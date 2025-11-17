<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\EventStoreAdapter;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Projecting\Config\ProjectionExecutorBuilder;

/**
 * Builder for EventStoreChannelAdapter projection executor.
 * Creates a projection that forwards all events to a streaming channel.
 *
 * @internal
 */
class EventStoreChannelAdapterProjectionBuilder implements ProjectionExecutorBuilder
{
    public function __construct(
        private EventStoreChannelAdapter $channelAdapter
    ) {
    }

    public function projectionName(): string
    {
        return $this->channelAdapter->getProjectionName();
    }

    public function asyncChannelName(): ?string
    {
        return null; // Channel adapters are always polling-based
    }

    public function partitionHeader(): ?string
    {
        return null; // Channel adapters don't support partitioning
    }

    public function automaticInitialization(): bool
    {
        return true;
    }

    public function batchSize(): int
    {
        return $this->channelAdapter->batchSize;
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        // Create the projection executor that forwards events to the streaming channel
        return new Definition(
            EventStoreChannelAdapterProjection::class,
            [
                Reference::toChannel($this->channelAdapter->streamChannelName),
                $this->channelAdapter->eventNames,
            ]
        );
    }
}
