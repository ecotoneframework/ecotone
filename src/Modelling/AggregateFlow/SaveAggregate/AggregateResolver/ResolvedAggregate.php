<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver;

use Ecotone\Api\EventSourcing\AppendCondition;
use Ecotone\Api\EventSourcing\Event;

/**
 * licence Apache-2.0
 */
final class ResolvedAggregate
{
    private ?int $versionAfterHandling;

    private ?AppendCondition $counterCapturedAtLoad = null;

    /**
     * @param object $aggregateInstance
     * @param array<string, mixed> $identifiers
     * @param Event[] $events
     */
    public function __construct(
        private AggregateClassDefinition $aggregateClassDefinition,
        private bool                     $isNewInstance,
        private object                   $aggregateInstance,
        private ?int                     $versionBeforeHandling,
        private array                    $identifiers,
        private array                    $events,
    ) {
        $this->versionAfterHandling = $this->versionBeforeHandling;
    }

    public function getAggregateClassName(): string
    {
        return $this->aggregateClassDefinition->getClassName();
    }

    public function isNewInstance(): bool
    {
        return $this->isNewInstance;
    }

    public function getAggregateClassDefinition(): AggregateClassDefinition
    {
        return $this->aggregateClassDefinition;
    }

    public function getIdentifiers(): array
    {
        return $this->identifiers;
    }

    public function getAggregateInstance(): object
    {
        return $this->aggregateInstance;
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    public function getVersionBeforeHandling(): ?int
    {
        return $this->versionBeforeHandling;
    }

    public function getVersionAfterHandling(): ?int
    {
        return $this->versionAfterHandling;
    }

    public function getCounterCapturedAtLoad(): AppendCondition
    {
        return $this->counterCapturedAtLoad ?? AppendCondition::empty();
    }

    public function withCounterCapturedAtLoad(AppendCondition $counterCapturedAtLoad): self
    {
        $clone = clone $this;
        $clone->counterCapturedAtLoad = $counterCapturedAtLoad;

        return $clone;
    }

    /**
     * @param array<string, mixed> $identifiers
     */
    public function withIdentifiers(array $identifiers): self
    {
        return (new self(
            $this->aggregateClassDefinition,
            $this->isNewInstance,
            $this->aggregateInstance,
            $this->versionBeforeHandling,
            $identifiers,
            $this->events
        ))->withCounterCapturedAtLoad($this->getCounterCapturedAtLoad());
    }

    public function withVersionAfterHandling(?int $versionAfterHandling): self
    {
        $clone = clone $this;
        $clone->versionAfterHandling = $versionAfterHandling;

        return $clone;
    }
}
