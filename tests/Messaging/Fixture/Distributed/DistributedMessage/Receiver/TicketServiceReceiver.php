<?php

namespace Test\Ecotone\Messaging\Fixture\Distributed\DistributedMessage\Receiver;

use Ecotone\Api\Attribute\Distributed;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\QueryHandler;

/**
 * licence Apache-2.0
 */
class TicketServiceReceiver
{
    public const CREATE_TICKET_ENDPOINT = 'createTicket';
    public const GET_TICKETS_COUNT      = 'getTicketsCount';

    private array $tickets = [];

    #[Distributed]
    #[InternalHandler(self::CREATE_TICKET_ENDPOINT)]
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
