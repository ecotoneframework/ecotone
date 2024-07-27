<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;

/**
 * licence Apache-2.0
 */
class PollingMetadataConverterBuilder implements ParameterConverterBuilder
{
    public function __construct(private string $parameterName)
    {
    }

    public function compile(InterfaceToCall $interfaceToCall): Reference
    {
        return new Reference(PollingMetadataConverter::class);
    }

    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }
}
