<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;

/**
 * licence Apache-2.0
 */
class AttributeDefinitionBuilder implements ParameterConverterBuilder
{
    public function __construct(private string $parameterName, private AttributeDefinition $attributeDefinition)
    {
    }

    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }

    public function compile(InterfaceToCall $interfaceToCall): Definition
    {
        return new Definition(ValueConverter::class, [$this->attributeDefinition]);
    }
}
