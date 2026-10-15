<?php

declare(strict_types=1);

namespace Ecotone\EventSourcing\EventStore;

use Ecotone\Api\EventSourcing\Event;

/**
 * licence Apache-2.0
 */
interface RecordedEventFormat
{
    public function recordedFrom(object|array $event): Event;

    public function recordedDataOf(Event $recordedEvent): Event;
}
