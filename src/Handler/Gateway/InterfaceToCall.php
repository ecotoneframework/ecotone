<?php

namespace Messaging\Handler\Gateway;
use Messaging\Support\Assert;
use Messaging\Support\InvalidArgumentException;

/**
 * Class InterfaceToCall
 * @package Messaging\Handler\Gateway
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


    /**
     * @return array|\ReflectionParameter[]
     */
    public function parameters() : array
    {
        $reflectionMethod = new \ReflectionMethod($this->interfaceName, $this->methodName);

        return $reflectionMethod->getParameters();
    }

    /**
     * @param array|NamedParameterConverter[] $namedParameterConverters
     * @throws InvalidArgumentException
     */
    public function checkCompatibilityWithMethodParameters(array $namedParameterConverters) : void
    {
        foreach ($this->parameters() as $parameter) {
            if (!$this->hasConverterWithName($parameter, $namedParameterConverters)) {
                throw new InvalidArgumentException("Missing argument converter for {$this} for parameter with name {$parameter->name}");
            }
        }
    }

    /**
     * @param \ReflectionParameter $parameter
     * @param array|NamedParameterConverter[] $namedParameterConverters
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
     * @return bool
     */
    public function hasOneParameter() : bool
    {
        $methodReflection = new \ReflectionMethod($this->interfaceName, $this->methodName);

        return $methodReflection->getNumberOfParameters() == 1;
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
    }

    public function __toString()
    {
        return "Interface {$this->interfaceName} with method {$this->methodName}";
    }
}