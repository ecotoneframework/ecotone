<?php


namespace Test\Ecotone\Modelling\Fixture\InterceptedEventAggregate;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\EventWasLogged;

class NotificationService
{
    private ?object $lastLog = null;

    private ?string $happenedAt = null;

    #[ServiceActivator("notify")]
    public function notify(array $logs, array $metadata) : void
    {
        $this->lastLog  = $logs[0];
        $this->happenedAt = $metadata["notificationTimestamp"];
    }

    #[EventHandler]
    public function store(EventWasLogged $event, array $metadata) : void
    {
        $this->lastLog = $event;
        $this->happenedAt  = $metadata["notificationTimestamp"];
    }

    #[QueryHandler("getLastLog")]
    public function getLogs() : array
    {
        return [
            "event" => $this->lastLog,
            "happenedAt" => $this->happenedAt
        ];
    }
}