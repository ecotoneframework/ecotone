<?php

declare(strict_types=1);

namespace Ecotone\Modelling\DecisionModel;

use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Api\Messaging\Message;
use Ecotone\EventSourcing\Tagging\AggregateCounterTags;
use Ecotone\Messaging\Handler\ClosureExpression\AttributeExpressionExecutor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\FetchAggregateConverter;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\AggregateIdString;

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
final class FetchedAggregateCounterCapture
{
    public function __construct(
        private readonly string $parameterName,
        private readonly string $aggregateClassName,
        private readonly AttributeExpressionExecutor $expressionExecutor,
        private readonly AggregateDefinitionRegistry $aggregateDefinitionRegistry,
        private readonly AggregateCounterTags $aggregateCounterTags,
    ) {
    }

    public function parameterName(): string
    {
        return $this->parameterName;
    }

    public function resolveCriteria(Message $message): ?EventCriteria
    {
        $identifiers = FetchAggregateConverter::identifiersFrom(
            $this->expressionExecutor->execute($message, ['value' => $message->getPayload()]),
            $this->aggregateClassName,
            $this->aggregateDefinitionRegistry,
        );
        $aggregateType = $this->aggregateCounterTags->aggregateTypeOfClass($this->aggregateClassName);

        if ($identifiers === null || $aggregateType === null) {
            return null;
        }

        $counterTag = $this->aggregateCounterTags->counterTagOf($aggregateType, AggregateIdString::from($identifiers));

        return EventCriteria::tag($counterTag['name'], $counterTag['value']);
    }
}
