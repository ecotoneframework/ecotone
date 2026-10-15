<?php

declare(strict_types=1);

namespace Ecotone\Modelling\DecisionModel;

use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\ClosureExpression\AttributeExpressionExecutor;
use Ecotone\Messaging\Handler\ExpressionEvaluationException;
use Ecotone\Messaging\Handler\ExpressionResult;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\FetchAggregateConverter;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\EventSourcingExecutor\ApplyMethodExecutor;

use function implode;
use function is_object;
use function sprintf;

use Throwable;

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
final class AggregateBackedDecisionModelLoader
{
    public function __construct(
        private readonly string $parameterName,
        private readonly string $modelClassName,
        private readonly bool $doesAllowNulls,
        private readonly DecisionModelDefinitionRegistry $decisionModelDefinitionRegistry,
        private readonly ApplyMethodExecutor $applyMethodExecutor,
        private readonly ParameterConverter $payloadConverter,
        private readonly AttributeExpressionExecutor $expressionExecutor,
        private readonly AggregateDefinitionRegistry $aggregateDefinitionRegistry,
    ) {
    }

    public function parameterName(): string
    {
        return $this->parameterName;
    }

    public function resolveInstance(Message $message): ?AggregateBackedDecisionModelInstance
    {
        $definition = $this->decisionModelDefinitionRegistry->getAggregateBacked($this->modelClassName);

        $identifiers = $this->expressionExecutor->hasExpression()
            ? $this->identifiersFromExpression($definition, $message)
            : $this->identifiersFromPayload($definition, $this->payloadConverter->getArgumentFrom($message));

        return $identifiers === null ? null : $definition->instanceFor($identifiers);
    }

    /**
     * @param Event[] $events
     */
    public function fold(array $events, ?object $snapshotState = null): object
    {
        return $this->applyMethodExecutor->fill($events, $snapshotState);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function identifiersFromExpression(AggregateBackedDecisionModelDefinition $definition, Message $message): ?array
    {
        $resolved = $this->expressionExecutor->execute($message, ['value' => $message->getPayload()]);

        try {
            $identifiers = FetchAggregateConverter::identifiersFrom(
                $resolved,
                $definition->aggregateClassName(),
                $this->aggregateDefinitionRegistry,
            );
        } catch (Throwable $exception) {
            throw ExpressionEvaluationException::wrapping($this->expressionExecutor->location(), $exception);
        }

        if ($identifiers !== null) {
            return $identifiers;
        }

        if ($this->doesAllowNulls) {
            return null;
        }

        throw ExpressionEvaluationException::because($this->expressionExecutor->location(), sprintf(
            'DecisionModel %s did not resolve an identifier of aggregate %s. The expression returned %s.',
            $this->modelClassName,
            $definition->aggregateClassName(),
            ExpressionResult::describe($resolved),
        ));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function identifiersFromPayload(AggregateBackedDecisionModelDefinition $definition, mixed $payload): ?array
    {
        $identifiers = [];
        foreach ($definition->identifierNames() as $identifierName) {
            $value = is_object($payload) ? MessageAggregateIdentifierResolver::resolve($identifierName, $payload) : null;

            if ($value === null) {
                if ($this->doesAllowNulls) {
                    return null;
                }

                throw ConfigurationException::create(sprintf(
                    "Could not resolve identifier '%s' of aggregate %s for DecisionModel %s from message %s. Name a property '%s', or map it explicitly with #[Fetch].",
                    $identifierName,
                    $definition->aggregateClassName(),
                    $this->modelClassName,
                    is_object($payload) ? $payload::class : gettype($payload),
                    implode("', '", MessageAggregateIdentifierResolver::candidatePropertyNamesFor($identifierName)),
                ));
            }

            $identifiers[$identifierName] = $value;
        }

        return $identifiers;
    }
}
