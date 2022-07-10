<?php


namespace Test\Ecotone\EventSourcing\Fixture\Ticket\Command;


class RegisterTicket
{
    private string $ticketId;
    private string $assignedPerson;
    private string $ticketType;

    public function __construct(string $ticketId, string $assignedPerson, string $ticketType)
    {
        $this->ticketId       = $ticketId;
        $this->assignedPerson = $assignedPerson;
        $this->ticketType     = $ticketType;
    }

    public function getTicketId(): string
    {
        return $this->ticketId;
    }

    public function getAssignedPerson(): string
    {
        return $this->assignedPerson;
    }

    public function getTicketType(): string
    {
        return $this->ticketType;
    }
}