<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Consumer;

/**
 * Tracks consumer position/offset for resumable message consumption
 * licence Apache-2.0
 */
interface ConsumerPositionTracker
{
    /**
     * Load the last committed position for a consumer
     *
     * @param string $consumerId Unique identifier for the consumer (endpoint ID)
     * @return string|null Last committed position, or null if no position stored
     */
    public function loadPosition(string $consumerId): ?string;

    /**
     * Save/commit the current position for a consumer
     *
     * @param string $consumerId Unique identifier for the consumer
     * @param string $position Position to commit (offset, timestamp, etc.)
     */
    public function savePosition(string $consumerId, string $position): void;

    /**
     * Delete position tracking for a consumer
     *
     * @param string $consumerId Consumer to reset
     */
    public function deletePosition(string $consumerId): void;
}
