<?php

namespace SimplyCodedSoftware\Messaging\Handler;

use SimplyCodedSoftware\Messaging\Future;
use SimplyCodedSoftware\Messaging\Handler\Gateway\NamedParameter;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class InterfaceToCall
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\Gateway
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
     * @return InterfaceToCall
     */
    public static function create(string $interfaceName, string $methodName) : self
    {
        return new self($interfaceName, $methodName);
    }

    public static function createFromObject($object, string $methodName) : self
    {
        return new self(get_class($object), $methodName);
    }

    public function doesItReturnValue() : bool
    {
        return $this->getReturnType() == 'void';
    }

    /**
     * @return array|\ReflectionParameter[]
     */
    public function parameters() : array
    {
        return $this->reflectionMethod()->getParameters();
    }

    /**
     * @return string
     */
    public function getFirstParameterName() : string
    {
        return $this->getFirstParameter()->getName();
    }

    /**
     * @return bool
     */
    public function hasFirstParameterMessageTypeHint() : bool
    {
        $firstParameter = $this->getFirstParameter();

        return (string)$firstParameter->getType() == Message::class;
    }

    /**
     * @return bool
     */
    public function doesItReturnFuture() : bool
    {
        return $this->getReturnType() == Future::class;
    }

    /**
     * @return bool
     */
    public function canItReturnNull() : bool
    {
        return $this->reflectionMethod()->getReturnType()->allowsNull();
    }

    /**
     * @return \ReflectionParameter
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function getFirstParameter() : \ReflectionParameter
    {
        if ($this->parameterAmount() < 1) {
            throw InvalidArgumentException::create("Expecting {$this} to have at least one parameter, but got none");
        }

        return $this->parameters()[0];
    }

    /**
     * @param array|NamedParameter[] $namedParameterConverters
     * @throws InvalidArgumentException
     */
    public function checkCompatibilityWithMethodParameters(array $namedParameterConverters) : void
    {
        $converterCount = count($namedParameterConverters);
        if ($this->hasSingleArguments() && $converterCount == 1) {
            return;
        }

        foreach ($this->parameters() as $parameter) {
            if (!$this->hasConverterWithName($parameter, $namedParameterConverters)) {
                throw new InvalidArgumentException("Missing argument converter for {$this} for parameter with name {$parameter->name}");
            }
        }
    }

    /**
     * @return bool
     */
    public function hasMoreThanOneParameter() : bool
    {
        return $this->parameterAmount() > 1;
    }

    /**
     * @return bool
     */
    public function hasSingleArguments() : bool
    {
        return $this->parameterAmount() == 1;
    }

    /**
     * @return int
     */
    private function parameterAmount() : int
    {
        return count($this->parameters());
    }

    /**
     * @param \ReflectionParameter $parameter
     * @param array|NamedParameter[] $namedParameterConverters
     * @return bool
     */
    private function hasConverterWithName(\ReflectionParameter $parameter, array $namedParameterConverters) : bool
    {
        foreach ($namedParameterConverters as $namedParameterConverter) {
            if ($namedParameterConverter->hasParameterName($parameter->getName())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $interfaceName
     * @param string $methodName
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function initialize(string $interfaceName, string $methodName) : void
    {
        $interfaceReflection = new \ReflectionClass($interfaceName);
        if (!$interfaceReflection->hasMethod($methodName)) {
            throw InvalidArgumentException::create("Interface {$interfaceName} has no method named {$methodName}");
        }

        $this->interfaceName = $interfaceName;
        $this->methodName = $methodName;

        if ($this->isReturnTypeUnknown()) {
            throw InvalidArgumentException::create("Return type for {$this} is unknown");
        }
    }

    /**
     * @return bool
     */
    private function isReturnTypeUnknown() : bool
    {
        $returnType = $this->getReturnType();

        return is_null($returnType) || $returnType === '';
    }

    public function __toString()
    {
        return "Interface {$this->interfaceName} with method {$this->methodName}";
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
     * @return string
     */
    private function getReturnType(): string
    {
        return (string)$this->reflectionMethod()->getReturnType();
    }
}