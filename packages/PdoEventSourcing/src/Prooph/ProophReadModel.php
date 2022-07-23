<?php

namespace Ecotone\EventSourcing\Prooph;

use Ecotone\EventSourcing\ProjectionLifeCycleConfiguration;
use Ecotone\EventSourcing\ProjectionRunningConfiguration;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Prooph\EventStore\Projection\ReadModel;

class ProophReadModel implements ReadModel
{
    public function __construct() {}

    public function init(): void
    {
        return;
    }

    public function isInitialized(): bool
    {
        return false;
    }

    public function reset(): void
    {
        return;
    }

    public function delete(): void
    {
        return;
    }

    public function stack(string $operation, ...$args): void
    {
    }

    public function persist(): void
    {
    }
}
