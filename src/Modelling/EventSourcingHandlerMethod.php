<?php

declare(strict_types=1);

namespace Ecotone\Modelling;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Type;

/**
 * licence Apache-2.0
 */
final class EventSourcingHandlerMethod
{
    /**
     * @param array<string> $interfaceParametersNames
     * @param array<ParameterConverter> $parameterConverters
     */
    public function __construct(
        private string $interfaceName,
        private string $methodName,
        private Type  $handledEventType,
        private array $interfaceParametersNames,
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
                $interfaceToCall->getInterfaceName(),
                $interfaceToCall->getMethodName(),
                $interfaceToCall->getFirstParameter()->getTypeDescriptor(),
                $interfaceToCall->getInterfaceParametersNames(),
                array_map(
                    fn (ParameterConverterBuilder $parameterConverterBuilder) => $parameterConverterBuilder->compile($interfaceToCall),
                    $parameterConverters
                ),
            ]
        );
    }

    public function canHandle(mixed $event): bool
    {
        return $this->handledEventType->accepts($event);
    }

    /**
     * @return array<ParameterConverter>
     */
    public function getParameterConverters(): array
    {
        return $this->parameterConverters;
    }

    public function getInterfaceParametersNames(): array
    {
        return $this->interfaceParametersNames;
    }

    public function parametersCount(): int
    {
        return count($this->parameterConverters);
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function __toString(): string
    {
        return "{$this->interfaceName}::{$this->methodName}";
    }
}
