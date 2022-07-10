<?php

namespace Test\Ecotone\Amqp\Fixture\DistributedEventBus\Receiver;

use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

class TicketServiceReceiver
{
    const CREATE_TICKET_ENDPOINT = "createTicket";
    const GET_TICKETS_COUNT      = "getTicketsCount";

    private array $tickets = [];

    #[Distributed]
    #[EventHandler("userService.*")]
    public function registerTicket(string $ticket) : void
    {
        $this->tickets[] = $ticket;
    }

    #[QueryHandler(self::GET_TICKETS_COUNT)]
    public function getTickets() : int
    {
        return count($this->tickets);
    }
}