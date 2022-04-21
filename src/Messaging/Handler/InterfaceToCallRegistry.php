<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\InMemoryReferenceTypeFromNameResolver;
use Ecotone\Messaging\Config\ReferenceTypeFromNameResolver;
use Ecotone\Messaging\MessagingException;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Test\Ecotone\Messaging\Fixture\Behat\GatewayInGatewayWithMessages\MessageBasedQueryBusExample;

/**
 * Class InterfaceToCallBuilder
 * @package Ecotone\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterfaceToCallRegistry
{
    const REFERENCE_NAME = "interfaceToCallRegistry";

    /**
     * @var InterfaceToCall[]
     */
    private array $interfacesToCall = [];
    private \Ecotone\Messaging\Config\ReferenceTypeFromNameResolver $referenceTypeFromNameResolver;
    private ?AnnotationFinder $annotationFinder;
    private ?self $preparedInterfaceToCallRegistry = null;
    private bool $isLocked;

    private function __construct(ReferenceTypeFromNameResolver $referenceTypeFromNameResolver, ?AnnotationFinder $annotationFinder, bool $isLocked)
    {
        $this->referenceTypeFromNameResolver = $referenceTypeFromNameResolver;
        $this->annotationFinder = $annotationFinder;
        $this->isLocked = $isLocked;
    }

    public static function createEmpty(): self
    {
        return new self(InMemoryReferenceTypeFromNameResolver::createEmpty(), null, false);
    }

    public static function createWith(ReferenceTypeFromNameResolver $referenceTypeFromNameResolver, AnnotationFinder $annotationFinder): self
    {
        return new self($referenceTypeFromNameResolver, $annotationFinder, false);
    }

    public static function createWithBackedBy(ReferenceTypeFromNameResolver $referenceTypeFromNameResolver, self $interfaceToCallRegistry): self
    {
        $self = new self($referenceTypeFromNameResolver,null, false);
        $self->preparedInterfaceToCallRegistry = $interfaceToCallRegistry;

        return $self;
    }

    /**
     * @param InterfaceToCall[] $interfacesToCall
     * @param bool $isLocked
     * @param ReferenceSearchService $referenceSearchService
     * @return InterfaceToCallRegistry
     */
    public static function createWithInterfaces(iterable $interfacesToCall, bool $isLocked, ReferenceSearchService $referenceSearchService): self
    {
        $self = new self(InMemoryReferenceTypeFromNameResolver::createFromReferenceSearchService($referenceSearchService), null, $isLocked);
        foreach ($interfacesToCall as $interfaceToCall) {
            $self->interfacesToCall[self::getName($interfaceToCall->getInterfaceName(), $interfaceToCall->getMethodName())] = $interfaceToCall;
        }

        return $self;
    }

    /**
     * @param string|object $interfaceName
     * @param string $methodName
     * @return string
     */
    private static function getName($interfaceName, string $methodName): string
    {
        if (is_object($interfaceName)) {
            $interfaceName = get_class($interfaceName);
        }

        return $interfaceName . $methodName;
    }

    /**
     * @param string|object $interfaceName
     * @return InterfaceToCall[]
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function getForAllPublicMethodOf($interfaceName): iterable
    {
        $interfaces = [];
        foreach ((new ReflectionClass($interfaceName))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$method->isConstructor()) {
                $interfaces[] = $this->getFor($interfaceName, $method->getName());
            }
        }

        return $interfaces;
    }

    /**
     * @param string|object $interfaceName
     * @param string $methodName
     * @return InterfaceToCall
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function getFor($interfaceName, string $methodName): InterfaceToCall
    {
        if (array_key_exists(self::getName($interfaceName, $methodName), $this->interfacesToCall)) {
            return $this->interfacesToCall[self::getName($interfaceName, $methodName)];
        } else if ($this->isLocked) {
            $interfaceName = is_object($interfaceName) ? get_class($interfaceName) : $interfaceName;
            throw ConfigurationException::create("There is problem with configuration. Interface to call {$interfaceName}:{$methodName} was never registered via related interfaces.");
        }

        if ($this->preparedInterfaceToCallRegistry) {
            $interfaceToCall = $this->preparedInterfaceToCallRegistry->getFor($interfaceName, $methodName);
        }else {
            if ($this->annotationFinder) {
                $interfaceToCall = InterfaceToCall::createWithAnnotationFinder($interfaceName, $methodName, $this->annotationFinder);
            } else {
                $interfaceToCall = InterfaceToCall::create($interfaceName, $methodName);
            }
        }

        $this->interfacesToCall[self::getName($interfaceName, $methodName)] = $interfaceToCall;

        return $interfaceToCall;
    }

    /**
     * @param string $referenceName
     * @param string $methodName
     * @return InterfaceToCall
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws ConfigurationException
     * @throws MessagingException
     * @throws \Ecotone\Messaging\Support\InvalidArgumentException
     */
    public function getForReferenceName(string $referenceName, string $methodName): InterfaceToCall
    {
        try {
            $objectClassType = $this->referenceTypeFromNameResolver->resolve($referenceName);
        } catch (ReferenceNotFoundException $exception) {
            throw ConfigurationException::create("Cannot find reference with name `$referenceName` for method {$methodName}. " . $exception->getMessage());
        }

        if (!$objectClassType->isClassOrInterface()) {
            throw new InvalidArgumentException("Reference {$referenceName} is not an object");
        }

        return $this->getFor($objectClassType->toString(), $methodName);
    }
}