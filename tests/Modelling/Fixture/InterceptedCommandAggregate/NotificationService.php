<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate;

use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\QueryHandler;

/**
 * licence Apache-2.0
 */
class NotificationService
{
    private ?object $lastLog = null;

    private ?string $happenedAt = null;

    #[InternalHandler('notify')]
    public function notify(array $logs, array $metadata): void
    {
        $this->lastLog  = $logs[0];
        $this->happenedAt = $metadata['notificationTimestamp'];
    }

    #[EventHandler]
    public function store(EventWasLogged $event, array $metadata): void
    {
        $this->lastLog = $event;
        $this->happenedAt  = $metadata['notificationTimestamp'];
    }

    #[QueryHandler('getLastLog')]
    public function getLogs(): array
    {
        return [
            'event' => $this->lastLog,
            'happenedAt' => $this->happenedAt,
        ];
    }
}
