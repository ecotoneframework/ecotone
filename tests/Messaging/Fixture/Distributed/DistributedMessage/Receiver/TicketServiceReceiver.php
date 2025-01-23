<?php

namespace Test\Ecotone\Messaging\Fixture\Distributed\DistributedMessage\Receiver;

use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\Attribute\QueryHandler;

/**
 * licence Apache-2.0
 */
class TicketServiceReceiver
{
    public const CREATE_TICKET_ENDPOINT = 'createTicket';
    public const GET_TICKETS_COUNT      = 'getTicketsCount';

    private array $tickets = [];

    #[Distributed]
    #[ServiceActivator(self::CREATE_TICKET_ENDPOINT)]
    public function registerTicket(string $ticket): void
    {
        $this->tickets[] = $ticket;
    }

    #[QueryHandler(self::GET_TICKETS_COUNT)]
    public function getTickets(): int
    {
        return count($this->tickets);
    }
}
