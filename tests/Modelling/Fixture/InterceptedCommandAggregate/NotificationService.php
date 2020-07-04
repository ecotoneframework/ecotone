<?php


namespace Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ServiceActivator;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

/**
 * @MessageEndpoint()
 */
class NotificationService
{
    private object $lastLog;

    private string $happenedAt;

    /**
     * @ServiceActivator(inputChannelName="notify")
     */
    public function notify(array $logs, array $metadata) : void
    {
        $this->lastLog[]  = $logs[0];
        $this->happenedAt = $metadata["notificationTimestamp"];
    }

    /**
     * @EventHandler()
     */
    public function store(EventWasLogged $event, array $metadata) : void
    {
        $this->lastLog = $event;
        $this->happenedAt  = $metadata["notificationTimestamp"];
    }

    /**
     * @QueryHandler("getLastLog")
     */
    public function getLogs() : array
    {
        return [
            "event" => $this->lastLog,
            "happenedAt" => $this->happenedAt
        ];
    }
}