<?php

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
declare(strict_types=1);

namespace Ecotone\Api\Projecting;

/**
 * licence Enterprise
 *
 * @link https://docs.ecotone.tech/modelling/event-sourcing/setting-up-projections/scaling-and-advanced#feeding-a-streaming-channel-from-the-event-store
 */
readonly class EventStreamingChannelAdapter
{
    private function __construct(
        public string  $streamChannelName,
        public string  $endpointId,
        public string  $fromStream,
        public ?string $aggregateType = null,
        public int     $batchSize = 100,
        public array   $eventNames = [],
    ) {
    }

    public static function create(
        string $streamChannelName,
        string $endpointId,
        string $fromStream,
        ?string $aggregateType = null,
    ): self {
        return new self($streamChannelName, $endpointId, $fromStream, $aggregateType);
    }

    public function withBatchSize(int $batchSize): self
    {
        return new self(
            $this->streamChannelName,
            $this->endpointId,
            $this->fromStream,
            $this->aggregateType,
            $batchSize,
            $this->eventNames,
        );
    }

    /**
     * @param array<string> $eventNames Glob patterns like ['Ticket.*', 'Order.Created']
     */
    public function withEventNames(array $eventNames): self
    {
        return new self(
            $this->streamChannelName,
            $this->endpointId,
            $this->fromStream,
            $this->aggregateType,
            $this->batchSize,
            $eventNames,
        );
    }

    /**
     * @internal
     */
    public function getProjectionName(): string
    {
        return 'event_streaming_channel_adapter_' . $this->endpointId;
    }
}
