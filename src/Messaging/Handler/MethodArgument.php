<?php

namespace Ecotone\Messaging\Handler;

/**
 * Class MethodArgument
 * @package Ecotone\Messaging\Handler\Gateway\Gateway
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class MethodArgument
{
    private function __construct(private string $parameterName, private mixed $value)
    {
    }

    public static function createWith(string $parameterName, mixed $value): self
    {
        return new self($parameterName, $value);
    }

    /**
     * @return string
     */
    public function getParameterName(): string
    {
        return $this->parameterName;
    }

    public function replaceValue(mixed $value): self
    {
        return self::createWith($this->parameterName, $value);
    }

    /**
     * @return mixed
     */
    public function value(): mixed
    {
        return $this->value;
    }
}
