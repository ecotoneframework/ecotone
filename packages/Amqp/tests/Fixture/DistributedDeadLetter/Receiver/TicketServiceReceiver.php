<?php

namespace Test\Ecotone\Amqp\Fixture\DistributedDeadLetter\Receiver;

use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\Attribute\QueryHandler;

class TicketServiceReceiver
{
    const CREATE_TICKET_ENDPOINT  = "createTicket";
    const GET_ERROR_TICKETS_COUNT = "getErrorTicketsCount";

    private array $tickets = [];

    #[Distributed]
    #[CommandHandler(self::CREATE_TICKET_ENDPOINT)]
    public function registerTicket(string $ticket) : void
    {
        throw new \InvalidArgumentException("Error during handling");
    }

    #[QueryHandler(self::GET_ERROR_TICKETS_COUNT)]
    public function getTickets() : int
    {
        return count($this->tickets);
    }

    #[ServiceActivator(TicketServiceMessagingConfiguration::DEAD_LETTER_CHANNEL)]
    public function registerErrorTicket(string $ticket) : void
    {
        $this->tickets[] = $ticket;
    }
}