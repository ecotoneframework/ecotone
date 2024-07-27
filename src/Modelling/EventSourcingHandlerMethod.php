<?php

declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;

final class EventSourcingHandlerMethod
{
    /**
     * @param InterfaceToCall $interfaceToCall
     * @param array<ParameterConverter> $parameterConverters
     */
    public function __construct(
        private InterfaceToCall $interfaceToCall,
        private array $parameterConverters,
    ) {
    }

    /**
     * @param array<ParameterConverterBuilder> $parameterConverters
     */
    public static function prepareDefinition(
        InterfaceToCall $interfaceToCall,
        array $parameterConverters,
    ): Definition {
        return new Definition(
            EventSourcingHandlerMethod::class,
            [
                Reference::toInterface($interfaceToCall->getInterfaceName(), $interfaceToCall->getMethodName()),
                array_map(
                    fn (ParameterConverterBuilder $parameterConverterBuilder) => $parameterConverterBuilder->compile($interfaceToCall),
                    $parameterConverters
                ),
            ]
        );
    }

    public function getInterfaceToCall(): InterfaceToCall
    {
        return $this->interfaceToCall;
    }

    /**
     * @return array<ParameterConverter>
     */
    public function getParameterConverters(): array
    {
        return $this->parameterConverters;
    }
}
