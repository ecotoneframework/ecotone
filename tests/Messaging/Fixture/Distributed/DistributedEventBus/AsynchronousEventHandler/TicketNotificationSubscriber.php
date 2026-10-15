<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\AsynchronousEventHandler;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\QueryHandler;
use Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverTicket\TicketRegistered;

/**
 * licence Apache-2.0
 */
final class TicketNotificationSubscriber
{
    public const GET_TICKET_NOTIFICATION_COUNT = 'getTicketNotificationCount';
    private int $counter = 0;

    #[Asynchronous('notification_channel')]
    #[EventHandler(endpointId: 'notificationSubscriber')]
    public function when(TicketRegistered $event): void
    {
        $this->counter++;
    }

    #[QueryHandler(self::GET_TICKET_NOTIFICATION_COUNT)]
    public function getCounter(): int
    {
        return $this->counter;
    }
}
