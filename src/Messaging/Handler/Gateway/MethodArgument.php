<?php

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;
use SimplyCodedSoftware\Messaging\Handler\InterfaceParameter;

/**
 * Class MethodArgument
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway\Gateway
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodArgument
{
    /**
     * @var InterfaceParameter
     */
    private $parameter;
    /**
     * @var mixed
     */
    private $value;

    /**
     * MethodArgument constructor.
     *
     * @param InterfaceParameter $parameter
     * @param mixed                $value
     */
    private function __construct(InterfaceParameter $parameter, $value)
    {
        $this->parameter = $parameter;
        $this->value     = $value;
    }

    /**
     * @param InterfaceParameter $parameterName
     * @param mixed                $value
     *
     * @return MethodArgument
     */
    public static function createWith(InterfaceParameter $parameterName, $value): self
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
     * @return InterfaceParameter
     */
    public function getParameter(): InterfaceParameter
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