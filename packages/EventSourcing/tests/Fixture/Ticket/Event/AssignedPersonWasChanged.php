<?php


namespace Test\Ecotone\EventSourcing\Fixture\Ticket\Event;


class AssignedPersonWasChanged
{
    private string $ticketId;
    private string $assignedPerson;

    public function __construct(string $ticketId, string $assignedPerson)
    {
        $this->ticketId       = $ticketId;
        $this->assignedPerson = $assignedPerson;
    }

    public function getTicketId(): string
    {
        return $this->ticketId;
    }

    public function getAssignedPerson(): string
    {
        return $this->assignedPerson;
    }
}