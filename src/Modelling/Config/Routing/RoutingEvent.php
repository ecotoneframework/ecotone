<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Modelling\Config\Routing;

use Ecotone\AnnotationFinder\AnnotatedFinding;

class RoutingEvent
{
    private bool $isCanceled = false;
    private bool $isPropagationStopped = false;

    /**
     * @param array<string> $routingKeys
     * @param int|int[] $priority
     */
    public function __construct(
        private BusRoutingMapBuilder $busRoutingMapBuilder,
        private readonly AnnotatedFinding $registration,
        private string $destinationChannel,
        private array $routingKeys,
        private int|array $priority
    ) {
    }

    /**
     * @return int|int[]
     */
    public function getPriority(): int|array
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function getRegistration(): AnnotatedFinding
    {
        return $this->registration;
    }

    public function isCanceled(): bool
    {
        return $this->isCanceled;
    }

    public function cancel(): void
    {
        $this->isCanceled = true;
    }

    public function getDestinationChannel(): string
    {
        return $this->destinationChannel;
    }

    public function setDestinationChannel(string $destinationChannel): void
    {
        $this->destinationChannel = $destinationChannel;
    }

    public function getRoutingKeys(): array
    {
        return $this->routingKeys;
    }

    public function setRoutingKeys(array $routingKeys): void
    {
        $this->routingKeys = $routingKeys;
    }

    public function isPropagationStopped(): bool
    {
        return $this->isPropagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->isPropagationStopped = true;
    }

    public function getBusRoutingMapBuilder(): BusRoutingMapBuilder
    {
        return $this->busRoutingMapBuilder;
    }
}
