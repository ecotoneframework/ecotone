<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\LicenceDecider;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\Repository\AllAggregateRepository;

/**
 * licence Enterprise
 */
class FetchAggregateConverterBuilder implements ParameterConverterBuilder
{
    private function __construct(
        private string $parameterName,
        private string $aggregateClassName,
        private string $expression
    ) {
    }

    public static function create(string $parameterName, string $aggregateClassName, string $expression): self
    {
        return new self($parameterName, $aggregateClassName, $expression);
    }

    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }

    public function compile(InterfaceToCall $interfaceToCall): Definition
    {
        return new Definition(FetchAggregateConverter::class, [
            new Reference(AllAggregateRepository::class),
            new Reference(ExpressionEvaluationService::REFERENCE),
            $this->aggregateClassName,
            $this->expression,
            $interfaceToCall->getParameterWithName($this->parameterName)->doesAllowNulls(),
            Reference::to(LicenceDecider::class),
            Reference::to(AggregateDefinitionRegistry::class),
        ]);
    }
}
