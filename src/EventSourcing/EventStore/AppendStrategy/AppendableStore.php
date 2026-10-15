<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\EventStore\AppendStrategy;

use Ecotone\Api\EventSourcing\AppendCondition;

/**
 * licence Apache-2.0
 */
interface AppendableStore
{
    /**
     * @param object[]|array[] $events
     */
    public function appendEventsUnconditionally(string $streamName, array $events): void;

    /**
     * @param object[]|array[] $events
     */
    public function appendEventsWithAggregateCondition(string $streamName, array $events, AppendCondition $appendCondition): void;

    /**
     * @param object[]|array[] $events
     */
    public function appendEventsWithTagCondition(string $streamName, array $events, ?AppendCondition $appendCondition): void;
}
