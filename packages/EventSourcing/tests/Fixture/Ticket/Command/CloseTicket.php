<?php


namespace Test\Ecotone\EventSourcing\Fixture\Ticket\Command;


class CloseTicket
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