<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Distributed\DistributedCommandBus\ReceiverEventHandler;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Test\Ecotone\Amqp\Fixture\DistributedCommandBus\Receiver\Event\TicketCreated;

/**
 * licence Apache-2.0
 */
final class TicketNotificationEventHandler
{
    public const GET_TICKETS_NOTIFICATION_COUNT      = 'getTicketsNotificationCount';

    private array $ticketNotifications = [];

    public function __construct(private array $delays = [])
    {

    }

    #[Asynchronous('async')]
    #[EventHandler(endpointId: 'notify')]
    public function notify(TicketCreated $event): void
    {
        $delay = array_shift($this->delays);
        if ($delay) {
            sleep($delay);
        }

        $this->ticketNotifications[] = $event->getTicket();
    }

    #[QueryHandler(self::GET_TICKETS_NOTIFICATION_COUNT)]
    public function getTicketsNotifications(): int
    {
        return count($this->ticketNotifications);
    }
}
