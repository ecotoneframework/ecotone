<?php

namespace Ecotone\Messaging\Handler;

/**
 * Class MethodArgument
 * @package Ecotone\Messaging\Handler\Gateway\Gateway
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MethodArgument
{
    private \Ecotone\Messaging\Handler\InterfaceParameter $parameter;
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
     * @param InterfaceParameter $parameter
     * @param mixed                $value
     *
     * @return MethodArgument
     */
    public static function createWith(InterfaceParameter $parameter, $value): self
    {
        return new self($parameter, $value);
    }

    /**
     * @return string
     */
    public function getParameterName(): string
    {
        return $this->parameter->getName();
    }

    /**
     * @param InterfaceParameter $interfaceParameterToCompare
     * @return bool
     */
    public function hasEqualTypeAs(InterfaceParameter $interfaceParameterToCompare) : bool
    {
        return $this->getInterfaceParameter()->hasEqualTypeAs($interfaceParameterToCompare);
    }

    /**
     * @return InterfaceParameter
     */
    public function getInterfaceParameter(): InterfaceParameter
    {
        return $this->parameter;
    }

    /**
     * @param mixed $value
     * @return MethodArgument
     */
    public function replaceValue($value) : self
    {
        return self::createWith($this->getInterfaceParameter(), $value);
    }

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }
}