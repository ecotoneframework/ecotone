<?php

namespace Messaging\Handler\Gateway;
use Messaging\Support\Assert;
use Messaging\Support\InvalidArgumentException;

/**
 * Class InterfaceToCall
 * @package Messaging\Handler\Gateway\Gateway
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

    public function isVoid() : bool
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
     * @param array|NamedParameter[] $namedParameterConverters
     * @throws InvalidArgumentException
     */
    public function checkCompatibilityWithMethodParameters(array $namedParameterConverters) : void
    {
        $converterCount = count($namedParameterConverters);
        if ($this->hasNoArguments() && $converterCount == 1) {
            return;
        }

        if ($converterCount > $this->parameterAmount()) {
            throw new InvalidArgumentException("There are more converters than parameters for {$this}");
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
    public function hasNoArguments() : bool
    {
        return $this->parameterAmount() == 0;
    }

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
     * @throws \Messaging\MessagingException
     */
    private function initialize(string $interfaceName, string $methodName) : void
    {
        Assert::isInterface($interfaceName, "Gateway should point to interface instead of got {$interfaceName}");

        $interfaceReflection = new \ReflectionClass($interfaceName);
        if (!$interfaceReflection->hasMethod($methodName)) {
            throw InvalidArgumentException::create("Interface for gateway {$interfaceName} has no method {$methodName}");
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