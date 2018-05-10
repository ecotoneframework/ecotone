<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler;

use SimplyCodedSoftware\IntegrationMessaging\Future;
use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class InterfaceToCall
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterfaceToCall
{
    /**
     * @var string
     */
    private $interfaceName;
    /**
     * @var string
     */
    private $methodName;

    /**
     * InterfaceToCall constructor.
     * @param string $interfaceName
     * @param string $methodName
     */
    private function __construct(string $interfaceName, string $methodName)
    {
        $this->initialize($interfaceName, $methodName);
    }

    /**
     * @param string $interfaceName
     * @param string $methodName
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function initialize(string $interfaceName, string $methodName): void
    {
        $interfaceReflection = new \ReflectionClass($interfaceName);
        if (!$interfaceReflection->hasMethod($methodName)) {
            throw InvalidArgumentException::create("Interface {$interfaceName} has no method named {$methodName}");
        }

        $this->interfaceName = $interfaceName;
        $this->methodName = $methodName;

// @TODO       turn off till php 7.2
//        if ($this->isReturnTypeUnknown()) {
//            throw InvalidArgumentException::create("Return type for {$this} is unknown");
//        }
    }

    /**
     * @return string
     */
    private function getReturnType(): string
    {
        return (string)$this->reflectionMethod()->getReturnType();
    }

    /**
     * @return \ReflectionMethod
     */
    private function reflectionMethod(): \ReflectionMethod
    {
        $reflectionMethod = new \ReflectionMethod($this->interfaceName, $this->methodName);
        return $reflectionMethod;
    }

    /**
     * @param string $interfaceName
     * @param string $methodName
     * @return InterfaceToCall
     */
    public static function create(string $interfaceName, string $methodName): self
    {
        return new self($interfaceName, $methodName);
    }

    public static function createFromObject($object, string $methodName): self
    {
        return new self(get_class($object), $methodName);
    }

    /**
     * @param string|object $unknownType
     * @param string $methodName
     *
     * @return InterfaceToCall
     */
    public static function createFromUnknownType($unknownType, string $methodName) : self
    {
        if (is_object($unknownType)) {
            return self::createFromObject($unknownType, $methodName);
        }

        return self::create($unknownType, $methodName);
    }

    /**
     * @return bool
     */
    public function isStaticallyCalled() : bool
    {
        return $this->reflectionMethod()->isStatic();
    }

    /**
     * @return bool
     */
    public function hasReturnValue() : bool
    {
        return !($this->getReturnType() == 'void');
    }

    /**
     * @return bool
     */
    public function hasReturnTypeVoid() : bool
    {
        return $this->getReturnType() == 'void';
    }

    /**
     * @return bool
     */
    public function hasReturnValueBoolean() : bool
    {
        return $this->getReturnType() == "bool";
    }

    /**
     * @return bool
     */
    public function doesItReturnArray() : bool
    {
        return $this->getReturnType() == "array";
    }

    /**
     * @return string
     */
    public function getFirstParameterName(): string
    {
        return $this->getFirstParameter()->getName();
    }

    /**
     * @return InterfaceParameter
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function getFirstParameter(): InterfaceParameter
    {
        if ($this->parameterAmount() < 1) {
            throw InvalidArgumentException::create("Expecting {$this} to have at least one parameter, but got none");
        }

        return InterfaceParameter::create($this->parameters()[0]);
    }

    /**
     * @return int
     */
    private function parameterAmount(): int
    {
        return count($this->parameters());
    }

    /**
     * @return array|\ReflectionParameter[]
     */
    public function parameters(): array
    {
        return $this->reflectionMethod()->getParameters();
    }

    /**
     * @return bool
     */
    public function hasFirstParameterMessageTypeHint(): bool
    {
        return $this->getFirstParameter()->isMessage();
    }

    /**
     * @return bool
     */
    public function doesItReturnFuture(): bool
    {
        return $this->getReturnType() == Future::class;
    }

    /**
     * @return bool
     */
    public function doesItReturnMessage() : bool
    {
        return $this->getReturnType() === Message::class || is_subclass_of($this->getReturnType(), Message::class);
    }

    /**
     * @return bool
     */
    public function canItReturnNull(): bool
    {
        return is_null($this->reflectionMethod()->getReturnType()) || $this->reflectionMethod()->getReturnType()->allowsNull();
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function getFirstParameterTypeHint() : string
    {
        if ($this->parameterAmount() < 1) {
            throw InvalidArgumentException::create("Trying to get first parameter, but has none");
        }

        return $this->getFirstParameter()->getTypeHint();
    }

    /**
     * @param string $parameterName
     *
     * @return InterfaceParameter
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function getParameterWithName(string $parameterName): InterfaceParameter
    {
        foreach ($this->parameters() as $parameter) {
            if ($parameter->getName() == $parameterName) {
                return InterfaceParameter::create($parameter);
            }
        }

        throw InvalidArgumentException::create($this . " doesn't have parameter with name {$parameterName}");
    }

    /**
     * @return bool
     */
    public function hasMoreThanOneParameter(): bool
    {
        return $this->parameterAmount() > 1;
    }

    /**
     * @return bool
     */
    public function hasNoParameters() : bool
    {
        return $this->parameterAmount() == 0;
    }

    /**
     * @return bool
     */
    public function hasSingleArgument(): bool
    {
        return $this->parameterAmount() == 1;
    }

    public function __toString()
    {
        return "Interface {$this->interfaceName} with method {$this->methodName}";
    }

    /**
     * @return bool
     */
    private function isReturnTypeUnknown(): bool
    {
        $returnType = $this->getReturnType();

        return is_null($returnType) || $returnType === '';
    }
}