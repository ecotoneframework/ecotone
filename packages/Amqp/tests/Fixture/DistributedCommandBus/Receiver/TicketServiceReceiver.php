<?php

namespace Test\Ecotone\Amqp\Fixture\DistributedCommandBus\Receiver;

use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\Attribute\QueryHandler;

class TicketServiceReceiver
{
    public const CREATE_TICKET_ENDPOINT = 'createTicket';
    public const GET_TICKETS_COUNT      = 'getTicketsCount';

    private array $tickets = [];

    #[Distributed]
    #[CommandHandler(self::CREATE_TICKET_ENDPOINT)]
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
