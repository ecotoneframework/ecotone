<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\ConfigurationException;
use SimplyCodedSoftware\Messaging\Config\InMemoryReferenceTypeFromNameResolver;
use SimplyCodedSoftware\Messaging\Config\ReferenceTypeFromNameResolver;

/**
 * Class InterfaceToCallBuilder
 * @package SimplyCodedSoftware\Messaging\Handler
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
     * InterfaceToCallRegistry constructor.
     * @param ReferenceTypeFromNameResolver $referenceTypeFromNameResolver
     */
    private function __construct(ReferenceTypeFromNameResolver $referenceTypeFromNameResolver)
    {
        $this->referenceTypeFromNameResolver = $referenceTypeFromNameResolver;
    }

    /**
     * @return InterfaceToCallRegistry
     */
    public static function createEmpty() : self
    {
        return new self(InMemoryReferenceTypeFromNameResolver::createEmpty());
    }

    /**
     * @param ReferenceTypeFromNameResolver $referenceTypeFromNameResolver
     * @return InterfaceToCallRegistry
     */
    public static function createWith(ReferenceTypeFromNameResolver $referenceTypeFromNameResolver) : self
    {
        return new self($referenceTypeFromNameResolver);
    }

    /**
     * @param iterable $interfacesToCall
     * @return InterfaceToCallRegistry
     */
    public static function createWithInterfaces(iterable $interfacesToCall) : self
    {
        $self = new self(InMemoryReferenceTypeFromNameResolver::createEmpty());
        $self->interfacesToCall = $interfacesToCall;

        return $self;
    }

    /**
     * @param string|object $interfaceName
     * @param string $methodName
     * @return InterfaceToCall
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function getFor($interfaceName, string $methodName) : InterfaceToCall
    {
        if (array_key_exists($this->getName($interfaceName, $methodName), $this->interfacesToCall)) {
            return $this->interfacesToCall[$this->getName($interfaceName, $methodName)];
        }

        $interfaceToCall = InterfaceToCall::create($interfaceName, $methodName);
        $this->interfacesToCall[$this->getName($interfaceName, $methodName)] = $interfaceToCall;

        return $interfaceToCall;
    }

    /**
     * @param string|object $interfaceName
     * @return InterfaceToCall[]
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function getForAllPublicMethodOf($interfaceName) : iterable
    {
        $interfaces = [];
        foreach ((new \ReflectionClass($interfaceName))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (!$method->isConstructor()) {
                $interfaces[] = $this->getFor($interfaceName, $method->getName());
            }
        }

        return $interfaces;
    }

    /**
     * @param string $referenceName
     * @param string $methodName
     * @return InterfaceToCall
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\Config\ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function getForReferenceName(string $referenceName, string $methodName) : InterfaceToCall
    {
        try {
            $objectClassType = $this->referenceTypeFromNameResolver->resolve($referenceName);
        }catch (ReferenceNotFoundException $exception) {
            throw ConfigurationException::create("Cannot find reference with name `$referenceName` for method {$methodName}. " . $exception->getMessage());
        }

        if (!$objectClassType->isObject()) {
            throw new \InvalidArgumentException("Reference {$referenceName} is not an object");
        }

        return $this->getFor($objectClassType->toString(), $methodName);
    }

    /**
     * @param string|object $interfaceName
     * @param string $methodName
     * @return string
     */
    private function getName($interfaceName, string $methodName) : string
    {
        if (is_object($interfaceName)) {
            $interfaceName = get_class($interfaceName);
        }

        return $interfaceName . $methodName;
    }
}