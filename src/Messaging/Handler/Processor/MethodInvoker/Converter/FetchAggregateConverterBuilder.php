<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Closure;
use Ecotone\Api\Attribute\Fetch;
use Ecotone\EventSourcing\Tagging\AggregateCounterTags;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\AttributeDeclaration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ClosureExpression\AttributeExpressionExecutorCompiler;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Type\ObjectType;
use Ecotone\Messaging\Handler\Type\UnionType;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\DecisionModel\FetchedAggregateCounterCapture;
use Ecotone\Modelling\Repository\AllAggregateRepository;

use function sprintf;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
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

    public function aggregateClassName(): string
    {
        return $this->aggregateClassName;
    }

    public function compileCounterCapture(InterfaceToCall $interfaceToCall): Definition
    {
        return new Definition(FetchedAggregateCounterCapture::class, [
            $this->parameterName,
            $this->aggregateClassName,
            AttributeExpressionExecutorCompiler::compile(new Fetch($this->expression), $this->attributeDeclaration, $interfaceToCall, $this->parameterName),
            Reference::to(AggregateDefinitionRegistry::class),
            Reference::to(AggregateCounterTags::class),
        ]);
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
            AttributeExpressionExecutorCompiler::compile(new Fetch($this->expression), $this->attributeDeclaration, $interfaceToCall, $this->parameterName),
            $interfaceToCall->getParameterWithName($this->parameterName)->doesAllowNulls(),
            Reference::to(AggregateDefinitionRegistry::class),
        ]);
    }

    public static function refusalWithoutEnterpriseLicence(Definition $compiledConverter): LicensingException
    {
        [, $aggregateClassName, $expressionExecutor] = $compiledConverter->getArguments();

        return LicensingException::create(sprintf(
            '%s is available as part of Ecotone Enterprise, and this application runs without an Enterprise licence. Either obtain an Enterprise licence (see https://docs.ecotone.tech/enterprise), or remove #[Fetch] and load %s in the handler body through its repository, for example a business interface method marked with #[Repository] that returns it.',
            AttributeExpressionExecutorCompiler::locationOf($expressionExecutor)->describe(),
            $aggregateClassName,
        ));
    }
}
