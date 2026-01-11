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
class EventStoreStreamingChannelAdapterBuilder implements ProjectionExecutorBuilder
{
    public function __construct(
        private EventStreamingChannelAdapter $channelAdapter
    ) {
    }

    public function projectionName(): string
    {
        return $this->channelAdapter->getProjectionName();
    }

    public function asyncChannelName(): ?string
    {
        return null;
    }

    public function partitionHeader(): ?string
    {
        return null;
    }

    public function automaticInitialization(): bool
    {
        return true;
    }

    public function eventLoadingBatchSize(): int
    {
        return $this->channelAdapter->batchSize;
    }

    public function backfillPartitionBatchSize(): int
    {
        return 100;
    }

    public function backfillAsyncChannelName(): ?string
    {
        return null;
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        return new Definition(
            EventStoreChannelAdapterProjection::class,
            [
                Reference::toChannel($this->channelAdapter->streamChannelName),
                $this->channelAdapter->eventNames,
            ]
        );
    }
}
