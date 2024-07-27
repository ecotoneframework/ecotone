<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;

/**
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class ValueBuilder implements ParameterConverterBuilder
{
    public function __construct(private string $parameterName, private mixed $staticValue)
    {
    }

    public static function create(string $parameterName, mixed $staticValue): self
    {
        return new self($parameterName, $staticValue);
    }

    /**
     * @inheritDoc
     */
    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }

    public function compile(InterfaceToCall $interfaceToCall): Definition
    {
        return new Definition(ValueConverter::class, [$this->staticValue]);
    }
}
