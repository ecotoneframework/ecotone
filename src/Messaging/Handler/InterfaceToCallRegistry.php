<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler;

use Doctrine\Common\Annotations\AnnotationException;
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
    private $interfacesToCall = [];
    /**
     * @var ReferenceTypeFromNameResolver
     */
    private $referenceTypeFromNameResolver;
    /**
     * @var bool
     */
    private $isLocked;

    /**
     * InterfaceToCallRegistry constructor.
     * @param ReferenceTypeFromNameResolver $referenceTypeFromNameResolver
     * @param bool $isLocked
     */
    private function __construct(ReferenceTypeFromNameResolver $referenceTypeFromNameResolver, bool $isLocked)
    {
        $this->referenceTypeFromNameResolver = $referenceTypeFromNameResolver;
        $this->isLocked = $isLocked;
    }

    /**
     * @return InterfaceToCallRegistry
     */
    public static function createEmpty(): self
    {
        return new self(InMemoryReferenceTypeFromNameResolver::createEmpty(), false);
    }

    /**
     * @param ReferenceTypeFromNameResolver $referenceTypeFromNameResolver
     * @return InterfaceToCallRegistry
     */
    public static function createWith(ReferenceTypeFromNameResolver $referenceTypeFromNameResolver): self
    {
        return new self($referenceTypeFromNameResolver, false);
    }

    /**
     * @param InterfaceToCall[] $interfacesToCall
     * @param bool $isLocked
     * @param ReferenceSearchService $referenceSearchService
     * @return InterfaceToCallRegistry
     */
    public static function createWithInterfaces(iterable $interfacesToCall, bool $isLocked, ReferenceSearchService $referenceSearchService): self
    {
        $self = new self(InMemoryReferenceTypeFromNameResolver::createFromReferenceSearchService($referenceSearchService), $isLocked);
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

        $interfaceToCall = InterfaceToCall::create($interfaceName, $methodName);
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