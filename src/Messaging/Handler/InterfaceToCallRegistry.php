<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Ecotone\AnnotationFinder\AnnotationResolver;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class InterfaceToCallBuilder
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class InterfaceToCallRegistry
{
    public const REFERENCE_NAME = 'interfaceToCallRegistry';

    /**
     * @var InterfaceToCall[]
     */
    private array $interfacesToCall = [];
    /**
     * @var ClassDefinition[]
     */
    private array $classDefinitions = [];
    private ?AnnotationResolver $annotationResolver;
    private ?self $preparedInterfaceToCallRegistry = null;
    private bool $isLocked;

    private function __construct(?AnnotationResolver $annotationResolver, bool $isLocked)
    {
        $this->annotationResolver = $annotationResolver;
        $this->isLocked = $isLocked;
    }

    public static function createEmpty(): self
    {
        return new self(null, false);
    }

    public static function createWith(AnnotationResolver $annotationResolver): self
    {
        return new self($annotationResolver, false);
    }

    public static function createWithBackedBy(self $interfaceToCallRegistry): self
    {
        $self = new self(null, false);
        $self->preparedInterfaceToCallRegistry = $interfaceToCallRegistry;

        return $self;
    }

    /**
     * @param InterfaceToCall[] $interfacesToCall
     * @param bool $isLocked
     * @param ReferenceSearchService $referenceSearchService
     * @return InterfaceToCallRegistry
     */
    public static function createWithInterfaces(iterable $interfacesToCall, bool $isLocked): self
    {
        $self = new self(null, $isLocked);
        foreach ($interfacesToCall as $interfaceToCall) {
            $self->interfacesToCall[self::getName($interfaceToCall->getInterfaceName(), $interfaceToCall->getMethodName())] = $interfaceToCall;
        }

        return $self;
    }

    private static function getName(string|object $interfaceName, string $methodName): string
    {
        if (is_object($interfaceName)) {
            $interfaceName = get_class($interfaceName);
        }

        return $interfaceName . $methodName;
    }

    public function getForReference(InterfaceToCallReference $interfaceToCallReference): InterfaceToCall
    {
        return $this->getFor($interfaceToCallReference->getClassName(), $interfaceToCallReference->getMethodName());
    }

    public function getFor(string|object $interfaceName, string $methodName): InterfaceToCall
    {
        if (array_key_exists(self::getName($interfaceName, $methodName), $this->interfacesToCall)) {
            return $this->interfacesToCall[self::getName($interfaceName, $methodName)];
        } elseif ($this->isLocked) {
            $interfaceName = is_object($interfaceName) ? get_class($interfaceName) : $interfaceName;
            throw ConfigurationException::create("There is problem with configuration. Interface to call {$interfaceName}:{$methodName} was never registered via related interfaces.");
        }

        if ($this->preparedInterfaceToCallRegistry) {
            $interfaceToCall = $this->preparedInterfaceToCallRegistry->getFor($interfaceName, $methodName);
        } else {
            if ($this->annotationResolver) {
                $interfaceToCall = InterfaceToCall::createWithAnnotationFinder($interfaceName, $methodName, $this->annotationResolver);
            } else {
                $interfaceToCall = InterfaceToCall::create($interfaceName, $methodName);
            }
        }

        $this->interfacesToCall[self::getName($interfaceName, $methodName)] = $interfaceToCall;

        return $interfaceToCall;
    }

    public function getClassDefinitionFor(Type $classType): ClassDefinition
    {
        if (array_key_exists($classType->toString(), $this->classDefinitions)) {
            return $this->classDefinitions[$classType->toString()];
        }

        if ($this->annotationResolver) {
            $classDefinition = ClassDefinition::createUsingAnnotationParser($classType, $this->annotationResolver);
        } else {
            $classDefinition = ClassDefinition::createFor($classType);
        }

        $this->classDefinitions[$classType->toString()] = $classDefinition;

        return $classDefinition;
    }

    public function getForAllPublicMethodOf(string|object $interfaceName): iterable
    {
        $interfaces = [];
        foreach ((new ReflectionClass($interfaceName))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (! $method->isConstructor()) {
                $interfaces[] = $this->getFor($interfaceName, $method->getName());
            }
        }

        return $interfaces;
    }
}
