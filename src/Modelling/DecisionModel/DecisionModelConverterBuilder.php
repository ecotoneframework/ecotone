<?php

declare(strict_types=1);

namespace Ecotone\Modelling\DecisionModel;

use Closure;
use Ecotone\Api\Attribute\Fetch;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\AttributeDeclaration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Handler\ClosureExpression\AttributeExpressionExecutor;
use Ecotone\Messaging\Handler\ClosureExpression\AttributeExpressionExecutorCompiler;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\ExpressionLocation;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Type\ObjectType;
use Ecotone\Messaging\Handler\Type\UnionType;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;

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
final class DecisionModelConverterBuilder implements ParameterConverterBuilder
{
    private function __construct(
        private readonly string $parameterName,
        private readonly string $modelClassName,
        private readonly bool $doesAllowNulls,
        private readonly string|Closure|null $fetchExpression,
        private readonly ?AttributeDeclaration $attributeDeclaration,
    ) {
    }

    public static function create(InterfaceParameter $parameter, string|Closure|null $fetchExpression = null, ?AttributeDeclaration $attributeDeclaration = null): self
    {
        $type = $parameter->getTypeDescriptor();
        if ($type instanceof UnionType) {
            $type = $type->withoutNull();
        }

        if (! $type instanceof ObjectType) {
            throw ConfigurationException::create(sprintf(
                'DecisionModel parameter %s must be typed with an object type, got %s.',
                $parameter->getName(),
                $parameter->getTypeDescriptor()->toString()
            ));
        }

        return new self($parameter->getName(), $type->toString(), $parameter->doesAllowNulls(), $fetchExpression, $attributeDeclaration);
    }

    public function isHandling(InterfaceParameter $parameter): bool
    {
        return $parameter->getName() === $this->parameterName;
    }

    public function parameterName(): string
    {
        return $this->parameterName;
    }

    public function modelClassName(): string
    {
        return $this->modelClassName;
    }

    public function compile(InterfaceToCall $interfaceToCall): Definition
    {
        return new Definition(DecisionModelConverter::class, [
            $this->parameterName,
        ]);
    }

    public function isBackedByAnAggregate(): bool
    {
        return DecisionModelReflection::backingAggregateOf($this->modelClassName) !== null;
    }

    public function compileLoader(InterfaceToCall $interfaceToCall): Definition
    {
        return new Definition(DecisionModelParameterLoader::class, $this->commonLoaderArguments($interfaceToCall));
    }

    public function compileAggregateBackedLoader(InterfaceToCall $interfaceToCall): Definition
    {
        return new Definition(AggregateBackedDecisionModelLoader::class, [
            ...$this->commonLoaderArguments($interfaceToCall),
            Reference::to(AggregateDefinitionRegistry::class),
        ]);
    }

    /**
     * @return array<int, mixed>
     */
    private function commonLoaderArguments(InterfaceToCall $interfaceToCall): array
    {
        $payloadParameterName = $interfaceToCall->getFirstParameter()->getName();

        return [
            $this->parameterName,
            $this->modelClassName,
            $this->doesAllowNulls,
            Reference::to(DecisionModelDefinitionRegistry::class),
            new Reference(DecisionModelExecutorRegistry::serviceIdFor($this->modelClassName)),
            PayloadBuilder::create($payloadParameterName)->compile($interfaceToCall),
            $this->fetchExpression !== null
                ? AttributeExpressionExecutorCompiler::compile(new Fetch($this->fetchExpression), $this->attributeDeclaration, $interfaceToCall, $this->parameterName)
                : new Definition(AttributeExpressionExecutor::class, [
                    Reference::to(ExpressionEvaluationService::REFERENCE),
                    ExpressionLocation::definitionForParameter(Fetch::class, $this->parameterName, $interfaceToCall->getInterfaceName(), $interfaceToCall->getMethodName(), null),
                ], factory: [AttributeExpressionExecutor::class, 'withoutExpression']),
        ];
    }
}
