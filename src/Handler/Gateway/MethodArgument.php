<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;

/**
 * Class MethodArgument
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\Gateway
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodArgument
{
    /**
     * @var \ReflectionParameter
     */
    private $parameter;
    /**
     * @var mixed
     */
    private $value;

    /**
     * MethodArgument constructor.
     *
     * @param \ReflectionParameter $parameter
     * @param mixed                $value
     */
    private function __construct(\ReflectionParameter $parameter, $value)
    {
        $this->parameter = $parameter;
        $this->value     = $value;
    }

    /**
     * @param \ReflectionParameter $parameterName
     * @param mixed                $value
     *
     * @return MethodArgument
     */
    public static function createWith(\ReflectionParameter $parameterName, $value): self
    {
        return new self($parameterName, $value);
    }

    /**
     * @return string
     */
    public function getParameterName(): string
    {
        return $this->parameter->getName();
    }

    /**
     * @return \ReflectionParameter
     */
    public function getParameter(): \ReflectionParameter
    {
        return $this->parameter;
    }

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }
}