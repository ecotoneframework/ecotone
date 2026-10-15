<?php

declare(strict_types=1);

namespace Ecotone\Modelling\DecisionModel;

use function array_is_list;
use function array_map;

use Ecotone\Api\EventSourcing\Event;
use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\Messaging\Message;
use Ecotone\EventSourcing\Tagging\EventTagValueNormalizer;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\ClosureExpression\AttributeExpressionExecutor;
use Ecotone\Messaging\Handler\ExpressionEvaluationException;
use Ecotone\Messaging\Handler\ExpressionResult;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Modelling\DecisionModel\Snapshot\DecisionModelFoldShape;
use Ecotone\Modelling\EventSourcingExecutor\ApplyMethodExecutor;

use function implode;
use function is_array;
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
final class DecisionModelParameterLoader
{
    public function __construct(
        private readonly string $parameterName,
        private readonly string $modelClassName,
        private readonly bool $doesAllowNulls,
        private readonly DecisionModelDefinitionRegistry $decisionModelDefinitionRegistry,
        private readonly ApplyMethodExecutor $applyMethodExecutor,
        private readonly ParameterConverter $payloadConverter,
        private readonly AttributeExpressionExecutor $expressionExecutor,
    ) {
    }

    public function parameterName(): string
    {
        return $this->parameterName;
    }

    public function modelClassName(): string
    {
        return $this->modelClassName;
    }

    public function foldShape(): string
    {
        $definition = $this->decisionModelDefinitionRegistry->get($this->modelClassName);

        return DecisionModelFoldShape::of($this->modelClassName, $definition->handledEventClasses(), $definition->tagNames());
    }

    public static function snapshotScopeKeyOf(EventCriteria $criteria): string
    {
        return implode('&', array_map(
            static fn (array $tag): string => $tag['name'] . '|' . $tag['value'],
            $criteria->tags,
        ));
    }

    public function resolveCriteria(Message $message): ?EventCriteria
    {
        $definition = $this->decisionModelDefinitionRegistry->get($this->modelClassName);
        $payload = $this->payloadConverter->getArgumentFrom($message);

        $tagValues = $this->expressionExecutor->hasExpression()
            ? $this->resolveTagValuesFromExpression($definition, $message)
            : $this->resolveTagValuesFromPayload($definition, $payload);

        return $tagValues === null ? null : $definition->toCriteria([...$tagValues, ...$definition->literalTagValues()]);
    }

    /**
     * @param Event[] $events
     */
    public function fold(array $events, ?object $snapshotState = null): object
    {
        return $this->applyMethodExecutor->fill($events, $snapshotState);
    }

    /**
     * @return array<string, string>|null
     */
    private function resolveTagValuesFromPayload(DecisionModelDefinition $definition, mixed $payload): ?array
    {
        $tagValues = [];
        foreach ($definition->tagNamesResolvedFromTheMessage() as $tagName) {
            $value = is_object($payload) ? $this->normalizedTagValue($tagName, MessageTagValueResolver::resolve($tagName, $payload)) : null;

            if ($value === null) {
                if ($this->doesAllowNulls) {
                    return null;
                }

                throw ConfigurationException::create(sprintf(
                    "Could not resolve tag '%s' for DecisionModel %s from message %s. Add #[EventTag('%s')] to a property, name a property '%sId', or use #[Fetch] to map it explicitly.",
                    $tagName,
                    $this->modelClassName,
                    is_object($payload) ? $payload::class : gettype($payload),
                    $tagName,
                    $tagName
                ));
            }

            $tagValues[$tagName] = $value;
        }

        return $tagValues;
    }

    /**
     * @return array<string, string>|null
     */
    private function resolveTagValuesFromExpression(DecisionModelDefinition $definition, Message $message): ?array
    {
        $resolved = $this->expressionExecutor->execute($message);
        $tagNames = $definition->tagNamesResolvedFromTheMessage();

        if ($tagNames === []) {
            return [];
        }

        $tagValues = [];
        foreach ($tagNames as $tagName) {
            $value = $this->normalizedTagValueFromExpression($tagName, $resolved, is_array($resolved) && ! array_is_list($resolved)
                ? ($resolved[$tagName] ?? null)
                : (count($tagNames) === 1 ? $resolved : null));

            if ($value === null) {
                if ($this->doesAllowNulls) {
                    return null;
                }

                throw ExpressionEvaluationException::because($this->expressionExecutor->location(), sprintf(
                    "DecisionModel %s did not resolve tag '%s'. The expression returned %s. %s",
                    $this->modelClassName,
                    $tagName,
                    ExpressionResult::describe($resolved),
                    $this->shapeGuidanceFor($tagNames),
                ));
            }

            $tagValues[$tagName] = $value;
        }

        return $tagValues;
    }

    /**
     * @param string[] $tagNames
     */
    private function shapeGuidanceFor(array $tagNames): string
    {
        return count($tagNames) === 1
            ? 'A single-tag model needs a scalar or Stringable value; declare the parameter nullable to let it contribute nothing.'
            : sprintf("This model is scoped by '%s'; the expression must return an array keyed by those names.", implode("' and '", $tagNames));
    }

    private function normalizedTagValueFromExpression(string $tagName, mixed $resolved, mixed $value): ?string
    {
        if (is_array($value)) {
            throw ExpressionEvaluationException::because($this->expressionExecutor->location(), sprintf(
                "DecisionModel %s cannot use the value resolved for tag '%s': the expression supplies several values, but a model is scoped by one value per tag. Inject the model once per value with #[Fetch], or express a boundary over several values with #[DecisionBoundary].",
                $this->modelClassName,
                $tagName,
            ));
        }

        try {
            return EventTagValueNormalizer::normalize($tagName, $value)[0] ?? null;
        } catch (ConfigurationException $exception) {
            throw ExpressionEvaluationException::because($this->expressionExecutor->location(), sprintf(
                "DecisionModel %s cannot use the value resolved for tag '%s': %s",
                $this->modelClassName,
                $tagName,
                $exception->getMessage(),
            ));
        }
    }

    private function normalizedTagValue(string $tagName, mixed $value): ?string
    {
        if (is_array($value)) {
            throw ConfigurationException::create(sprintf(
                "Could not resolve tag '%s' for DecisionModel %s: the message supplies several values, but a model is scoped by one value per tag. Inject the model once per value with #[Fetch], or express a boundary over several values with #[DecisionBoundary].",
                $tagName,
                $this->modelClassName,
            ));
        }

        try {
            return EventTagValueNormalizer::normalize($tagName, $value)[0] ?? null;
        } catch (ConfigurationException $exception) {
            throw ConfigurationException::create(sprintf(
                'Could not resolve tag \'%s\' for DecisionModel %s: %s',
                $tagName,
                $this->modelClassName,
                $exception->getMessage(),
            ));
        }
    }
}
