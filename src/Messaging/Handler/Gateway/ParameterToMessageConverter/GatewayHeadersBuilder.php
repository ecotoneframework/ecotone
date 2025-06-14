<?php

namespace Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter;

use Ecotone\Messaging\Attribute\BusinessMethod;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayParameterConverterBuilder;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\QueryBus;

/**
 * Class GatewayHeaderArrayBuilder
 * @package Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class GatewayHeadersBuilder implements GatewayParameterConverterBuilder
{
    private string $parameterName;

    /**
     * HeaderMessageParameter constructor.
     * @param string $parameterName
     */
    private function __construct(string $parameterName)
    {
        $this->parameterName = $parameterName;
    }

    /**
     * @param string $parameterName
     * @return self
     */
    public static function create(string $parameterName): self
    {
        return new self($parameterName);
    }

    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $this->parameterName === $parameter->getName();
    }

    public function compile(MessagingContainerBuilder $builder, InterfaceToCall $interfaceToCall): Definition
    {
        return new Definition(GatewayHeadersConverter::class, [
            $this->parameterName,
            $this->isStartingNewFlow($interfaceToCall),
        ]);
    }

    private function isStartingNewFlow(InterfaceToCall $interfaceToCall): bool
    {
        foreach ([BusinessMethod::class] as $attribute) {
            if ($interfaceToCall->hasAnnotation(TypeDescriptor::create($attribute))) {
                return true;
            }
        }

        foreach ([CommandBus::class, EventBus::class, QueryBus::class] as $gateway) {
            if ($interfaceToCall->getInterfaceType()->isCompatibleWith(TypeDescriptor::create($gateway))) {
                return true;
            }
        }

        return false;
    }
}
