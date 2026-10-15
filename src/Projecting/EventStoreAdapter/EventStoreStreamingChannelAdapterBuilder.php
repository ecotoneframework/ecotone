<?php

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
declare(strict_types=1);

namespace Ecotone\Projecting\EventStoreAdapter;

use Ecotone\Api\Projecting\EventStreamingChannelAdapter;
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

    public function isPartitioned(): bool
    {
        return false;
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

    public function rebuildPartitionBatchSize(): int
    {
        return 100;
    }

    public function rebuildAsyncChannelName(): ?string
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
