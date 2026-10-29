<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Closure;
use Ecotone\Messaging\Attribute\Parameter\Fetch;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\AttributeDeclaration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\LicenceDecider;
use Ecotone\Messaging\Handler\ClosureExpression\AttributeExpressionExecutorCompiler;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Type\ObjectType;
use Ecotone\Messaging\Handler\Type\UnionType;
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
        private string|Closure $expression,
        private ?AttributeDeclaration $attributeDeclaration
    ) {
    }

    public static function create(InterfaceParameter $parameter, string|Closure $expression, ?AttributeDeclaration $attributeDeclaration = null): self
    {
        $type = $parameter->getTypeDescriptor();
        if ($type instanceof UnionType) {
            $type = $type->withoutNull();
        }
        if (! $type instanceof ObjectType) {
            throw ConfigurationException::create('FetchAggregate can be used only with object type hint. ' . $parameter->getName() . ' is using ' . $parameter->getTypeDescriptor()->toString());
        }

        return new self($parameter->getName(), $type->toString(), $expression, $attributeDeclaration);
    }

    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }

    public function compile(InterfaceToCall $interfaceToCall): Definition
    {
        if ($this->expression instanceof Closure && $this->attributeDeclaration === null) {
            throw ConfigurationException::create("Closure expression inside Fetch attribute is not supported for parameter `{$this->parameterName}` in this context.");
        }

        return new Definition(FetchAggregateConverter::class, [
            new Reference(AllAggregateRepository::class),
            $this->aggregateClassName,
            AttributeExpressionExecutorCompiler::compile(new Fetch($this->expression), $this->attributeDeclaration),
            $interfaceToCall->getParameterWithName($this->parameterName)->doesAllowNulls(),
            Reference::to(LicenceDecider::class),
            Reference::to(AggregateDefinitionRegistry::class),
        ]);
    }
}
