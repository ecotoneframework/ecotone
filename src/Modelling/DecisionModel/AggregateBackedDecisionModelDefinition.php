<?php

declare(strict_types=1);

namespace Ecotone\Modelling\DecisionModel;

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
final class AggregateBackedDecisionModelDefinition
{
    /**
     * @param class-string $className
     * @param class-string $aggregateClassName
     * @param string[] $identifierNames
     * @param class-string[] $handledEventClasses
     */
    public function __construct(
        private readonly string $className,
        private readonly string $aggregateClassName,
        private readonly string $aggregateType,
        private readonly string $streamName,
        private readonly array $identifierNames,
        private readonly array $handledEventClasses,
    ) {
    }

    public function className(): string
    {
        return $this->className;
    }

    public function aggregateClassName(): string
    {
        return $this->aggregateClassName;
    }

    public function aggregateType(): string
    {
        return $this->aggregateType;
    }

    public function streamName(): string
    {
        return $this->streamName;
    }

    /**
     * @return string[]
     */
    public function identifierNames(): array
    {
        return $this->identifierNames;
    }

    /**
     * @return class-string[]
     */
    public function handledEventClasses(): array
    {
        return $this->handledEventClasses;
    }

    /**
     * @param array<string, mixed> $identifiers
     */
    public function instanceFor(array $identifiers): AggregateBackedDecisionModelInstance
    {
        return new AggregateBackedDecisionModelInstance(
            $this->className,
            $this->aggregateClassName,
            $this->aggregateType,
            $this->streamName,
            $identifiers,
            $this->handledEventClasses,
        );
    }
}
