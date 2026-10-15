<?php

declare(strict_types=1);

namespace Ecotone\Modelling\DecisionModel;

use Ecotone\Api\EventSourcing\EventCriteria;
use Ecotone\Modelling\AggregateIdString;
use Ecotone\Modelling\DecisionModel\Snapshot\DecisionModelFoldShape;

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
final class AggregateBackedDecisionModelInstance
{
    /**
     * @param class-string $modelClassName
     * @param class-string $aggregateClassName
     * @param array<string, mixed> $identifiers
     * @param class-string[] $handledEventClasses
     */
    public function __construct(
        private readonly string $modelClassName,
        private readonly string $aggregateClassName,
        private readonly string $aggregateType,
        private readonly string $streamName,
        private readonly array $identifiers,
        private readonly array $handledEventClasses,
    ) {
    }

    public function instanceKey(): string
    {
        return sprintf('%s|%s|%s', $this->streamName, $this->aggregateType, $this->aggregateId());
    }

    public function snapshotScopeKey(): string
    {
        return sprintf('%s|%s', $this->aggregateType, $this->aggregateId());
    }

    public function foldShape(): string
    {
        return DecisionModelFoldShape::of($this->modelClassName, $this->handledEventClasses, [$this->aggregateType]);
    }

    public function modelClassName(): string
    {
        return $this->modelClassName;
    }

    public function captureCriteria(): EventCriteria
    {
        return EventCriteria::aggregate($this->aggregateClassName, $this->identifiers);
    }

    public function streamName(): string
    {
        return $this->streamName;
    }

    public function aggregateType(): string
    {
        return $this->aggregateType;
    }

    public function aggregateId(): string
    {
        return AggregateIdString::from($this->identifiers);
    }

    /**
     * @return class-string[]
     */
    public function handledEventClasses(): array
    {
        return $this->handledEventClasses;
    }

    public function describeInstance(): string
    {
        return sprintf('%s %s', $this->aggregateType, $this->aggregateId());
    }
}
