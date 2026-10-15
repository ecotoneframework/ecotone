<?php

declare(strict_types=1);

namespace Ecotone\Modelling\DecisionModel;

use Ecotone\Messaging\Config\Container\Definition;

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
final class DecisionModelHandler
{
    /**
     * @param Definition[] $modelLoaderDefinitions
     * @param Definition[] $aggregateBackedModelLoaderDefinitions
     * @param class-string[] $ambiguouslyDuplicatedModelClasses
     * @param Definition[] $fetchedAggregateCaptureDefinitions
     * @param class-string[] $fetchedAggregateClasses
     * @param Definition[] $decisionBoundaryDefinitions
     */
    public function __construct(
        private readonly string $className,
        private readonly string $methodName,
        private readonly array $modelLoaderDefinitions,
        private readonly array $aggregateBackedModelLoaderDefinitions,
        private readonly bool $appendsItsResult,
        private readonly array $ambiguouslyDuplicatedModelClasses = [],
        private readonly array $fetchedAggregateCaptureDefinitions = [],
        private readonly array $fetchedAggregateClasses = [],
        private readonly array $decisionBoundaryDefinitions = [],
    ) {
    }

    public static function keyFor(string $className, string $methodName): string
    {
        return sprintf('%s::%s', $className, $methodName);
    }

    public function key(): string
    {
        return self::keyFor($this->className, $this->methodName);
    }

    public function className(): string
    {
        return $this->className;
    }

    public function methodName(): string
    {
        return $this->methodName;
    }

    /**
     * @return Definition[]
     */
    public function modelLoaderDefinitions(): array
    {
        return $this->modelLoaderDefinitions;
    }

    /**
     * @return Definition[]
     */
    public function aggregateBackedModelLoaderDefinitions(): array
    {
        return $this->aggregateBackedModelLoaderDefinitions;
    }

    /**
     * @return Definition[]
     */
    public function fetchedAggregateCaptureDefinitions(): array
    {
        return $this->fetchedAggregateCaptureDefinitions;
    }

    /**
     * @return class-string[]
     */
    public function fetchedAggregateClasses(): array
    {
        return $this->fetchedAggregateClasses;
    }

    /**
     * @return Definition[]
     */
    public function decisionBoundaryDefinitions(): array
    {
        return $this->decisionBoundaryDefinitions;
    }

    public function loadsBeforeInvocation(): bool
    {
        return $this->modelLoaderDefinitions !== [] || $this->aggregateBackedModelLoaderDefinitions !== [] || $this->fetchedAggregateCaptureDefinitions !== [] || $this->decisionBoundaryDefinitions !== [];
    }

    public function appendsItsResult(): bool
    {
        return $this->appendsItsResult;
    }

    /**
     * @return class-string[]
     */
    public function ambiguouslyDuplicatedModelClasses(): array
    {
        return $this->ambiguouslyDuplicatedModelClasses;
    }
}
