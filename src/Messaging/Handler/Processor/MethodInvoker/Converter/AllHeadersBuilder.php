<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;

/**
 * licence Apache-2.0
 */
class AllHeadersBuilder implements ParameterConverterBuilder
{
    private function __construct(private string $parameterName)
    {
    }

    /**
     * @param string $parameterName
     *
     * @return AllHeadersBuilder
     */
    public static function createWith(string $parameterName): self
    {
        return new self($parameterName);
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
        return new Definition(AllHeadersConverter::class);
    }
}
