<?php

namespace Messaging\Handler\Gateway;
use Messaging\Support\Assert;

/**
 * Class MethodArgument
 * @package Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodArgument
{
    /**
     * @var string
     */
    private $parameterName;
    /**
     * @var mixed
     */
    private $value;

    /**
     * MethodArgument constructor.
     * @param string $parameterName
     * @param mixed $value
     */
    private function __construct(string $parameterName, $value)
    {
        $this->value = $value;

        $this->initialize($parameterName);
    }

    /**
     * @param string $parameterName
     * @param $value
     * @return MethodArgument
     */
    public static function createWith(string $parameterName, $value) : self
    {
        return new self($parameterName, $value);
    }

    /**
     * @return string
     */
    public function getParameterName() : string
    {
        return $this->parameterName;
    }

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * @param string $parameterName
     */
    private function initialize(string $parameterName) : void
    {
        Assert::notNullAndEmpty($parameterName, "Argument to method must contain parameter name");

        $this->parameterName = $parameterName;
    }
}