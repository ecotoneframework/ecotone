<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Api\Messaging\Message;
use Ecotone\Api\Modelling\AggregateNotFoundException;
use Ecotone\Messaging\Handler\ClosureExpression\AttributeExpressionExecutor;
use Ecotone\Messaging\Handler\ExpressionEvaluationException;
use Ecotone\Messaging\Handler\ExpressionResult;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\Repository\AllAggregateRepository;
use InvalidArgumentException;
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
class FetchAggregateConverter implements ParameterConverter
{
    public function __construct(
        private AllAggregateRepository $aggregateRepository,
        private string $aggregateClassName,
        private AttributeExpressionExecutor $expressionExecutor,
        private bool $doesAllowsNull,
        private AggregateDefinitionRegistry $aggregateDefinitionRegistry,
    ) {
    }

    public function getArgumentFrom(Message $message): ?object
    {
        $resolvedIdentifiers = $this->expressionExecutor->execute($message, ['value' => $message->getPayload()]);

        try {
            $identifiers = self::identifiersFrom($resolvedIdentifiers, $this->aggregateClassName, $this->aggregateDefinitionRegistry);
        } catch (Throwable $exception) {
            throw ExpressionEvaluationException::wrapping($this->expressionExecutor->location(), $exception);
        }

        if ($identifiers === null) {
            if (! $this->doesAllowsNull) {
                throw ExpressionEvaluationException::because($this->expressionExecutor->location(), sprintf(
                    'Aggregate %s cannot be fetched: the expression returned %s. Declare the parameter nullable to accept a missing identifier.',
                    $this->aggregateClassName,
                    ExpressionResult::describe($resolvedIdentifiers),
                ));
            }

            return null;
        }

        $resolvedAggregate = $this->aggregateRepository->findBy(
            $this->aggregateClassName,
            $identifiers
        );

        if (! $resolvedAggregate && ! $this->doesAllowsNull) {
            $identifiersString = is_array($identifiers) ? json_encode($identifiers) : (string) $identifiers;
            throw new AggregateNotFoundException("Aggregate {$this->aggregateClassName} was not found for identifiers {$identifiersString}.");
        }

        return $resolvedAggregate?->getAggregateInstance();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function identifiersFrom(mixed $resolvedIdentifiers, string $aggregateClassName, AggregateDefinitionRegistry $aggregateDefinitionRegistry): ?array
    {
        if ($resolvedIdentifiers === null) {
            return null;
        }

        if (is_array($resolvedIdentifiers)) {
            return $resolvedIdentifiers;
        }

        $identifierMapping = $aggregateDefinitionRegistry->getFor($aggregateClassName)->getAggregateIdentifierMapping();
        if (count($identifierMapping) > 1) {
            throw new InvalidArgumentException("Can't fetch aggregate {$aggregateClassName} as it has multiple identifiers. Please provide array of identifiers.");
        }

        return [array_key_first($identifierMapping) => $resolvedIdentifiers];
    }

}
