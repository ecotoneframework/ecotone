<?php

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Config\Container\AttributeReference;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;

use function get_class;

/**
 * licence Apache-2.0
 */
class AttributeBuilder implements ParameterConverterBuilder
{
    public function __construct(private string $parameterName, private object $attributeInstance, private string $className, private ?string $methodName = null)
    {
    }

    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }

    public function compile(InterfaceToCall $interfaceToCall): Definition
    {
        return new Definition(ValueConverter::class, [new AttributeReference(get_class($this->attributeInstance), $this->className, $this->methodName)]);
    }
}
