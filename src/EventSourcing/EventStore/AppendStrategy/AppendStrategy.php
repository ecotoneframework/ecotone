<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\EventStore\AppendStrategy;

use Ecotone\Api\EventSourcing\AppendCondition;

/**
 * licence Apache-2.0
 */
interface AppendStrategy
{
    /**
     * @param object[]|array[] $events
     */
    public function append(AppendableStore $store, string $streamName, array $events, ?AppendCondition $appendCondition): void;
}
