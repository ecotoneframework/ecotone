<?php

namespace SimplyCodedSoftware\Messaging\Handler;

/**
 * Class InterfaceParameter
 * @package SimplyCodedSoftware\Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterfaceParameter
{
    /**
     * @var \ReflectionParameter
     */
    private $parameter;

    /**
     * InterfaceParameter constructor.
     * @param \ReflectionParameter $reflectionParameter
     */
    private function __construct(\ReflectionParameter $reflectionParameter)
    {
        $this->parameter = $reflectionParameter;
    }

    /**
     * @param \ReflectionParameter $reflectionParameter
     * @return InterfaceParameter
     */
    public static function create(\ReflectionParameter $reflectionParameter) : self
    {
        return new self($reflectionParameter);
    }

    /**
     * @return \ReflectionParameter
     */
    public function getReflectionParameter() : \ReflectionParameter
    {
        return $this->parameter;
    }

    /**
     * @return bool
     */
    public function isNullable() : bool
    {
        return $this->parameter->allowsNull();
    }

    /**
     * @return string
     */
    public function getTypeHint() : string
    {
        return (string)$this->parameter->getType();
    }

    /**
     * @return bool
     */
    public function isObjectTypeHint() : bool
    {
        return class_exists($this->getTypeHint());
    }
}