<?php

namespace Test\Ecotone\EventSourcing\Fixture\Ticket\Event;

class TicketWasClosed
{
    private string $ticketId;

    public function __construct(string $ticketId)
    {
        $this->ticketId = $ticketId;
    }

    public function getTicketId(): string
    {
        return $this->ticketId;
    }
}
