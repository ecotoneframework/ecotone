<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Endpoint\PollingConsumer\AsyncEndpointAnnotationContext;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;

/**
 * licence Enterprise
 */
class AsyncEndpointAnnotationBuilder implements ParameterConverterBuilder
{
    public function __construct(
        private string $parameterName,
        private string $attributeClassName,
    ) {
    }

    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }

    public function compile(InterfaceToCall $interfaceToCall): Definition
    {
        return new Definition(AsyncEndpointAnnotationConverter::class, [
            Reference::to(AsyncEndpointAnnotationContext::class),
            $this->attributeClassName,
        ]);
    }
}
