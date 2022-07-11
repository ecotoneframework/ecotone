<?php

namespace Ecotone\EventSourcing\Config\InboundChannelAdapter;

use Ecotone\EventSourcing\ProjectionLifeCycleConfiguration;
use Ecotone\EventSourcing\ProjectionRunningConfiguration;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Prooph\EventStore\Projection\ReadModel;

class ProophReadModel implements ReadModel
{
    public function __construct(
        private MessagingEntrypoint $messagingEntrypoint,
        private ProjectionLifeCycleConfiguration $projectionLifeCycleConfiguration,
        private ProjectionRunningConfiguration $projectionRunningConfiguration
    ) {
    }

    public function init(): void
    {
        if (! $this->projectionLifeCycleConfiguration->getInitializationRequestChannel()) {
            return;
        }

        if (! $this->projectionRunningConfiguration->isInitializedOnStartup()) {
            return;
        }

        $this->messagingEntrypoint->send([], $this->projectionLifeCycleConfiguration->getInitializationRequestChannel());
    }

    public function isInitialized(): bool
    {
        return false;
    }

    public function reset(): void
    {
        if (! $this->projectionLifeCycleConfiguration->getResetRequestChannel()) {
            return;
        }

        $this->messagingEntrypoint->send([], $this->projectionLifeCycleConfiguration->getResetRequestChannel());
    }

    public function delete(): void
    {
        if (! $this->projectionLifeCycleConfiguration->getDeleteRequestChannel()) {
            return;
        }

        $this->messagingEntrypoint->send([], $this->projectionLifeCycleConfiguration->getDeleteRequestChannel());
    }

    public function stack(string $operation, ...$args): void
    {
    }

    public function persist(): void
    {
    }
}
