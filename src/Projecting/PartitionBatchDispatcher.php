<?php

declare(strict_types=1);

namespace Ecotone\Projecting;

use Ecotone\Api\Messaging\Message;

/**
 * licence Apache-2.0
 */
interface PartitionBatchDispatcher
{
    /**
     * @param list<array<string, mixed>> $batches
     */
    public function dispatch(string $projectionName, string $operation, ?string $asyncChannelName, Message $prepare, array $batches): void;
}
