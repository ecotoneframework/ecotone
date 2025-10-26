<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Consumer\InMemory;

use Ecotone\Messaging\Consumer\ConsumerPositionTracker;

/**
 * In-memory implementation of position tracker for testing
 * licence Apache-2.0
 */
class InMemoryConsumerPositionTracker implements ConsumerPositionTracker
{
    /**
     * @var array<string, string> Map of consumerId => position
     */
    private array $positions = [];

    public function loadPosition(string $consumerId): ?string
    {
        return $this->positions[$consumerId] ?? null;
    }

    public function savePosition(string $consumerId, string $position): void
    {
        $this->positions[$consumerId] = $position;
    }

    public function deletePosition(string $consumerId): void
    {
        unset($this->positions[$consumerId]);
    }
}
