<?php

namespace Test\Ecotone\EventSourcing\Fixture\Ticket;

use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\WithAggregateVersioning;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Command\ChangeAssignedPerson;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Command\CloseTicket;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Command\RegisterTicket;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Event\AssignedPersonWasChanged;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Event\TicketWasClosed;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Event\TicketWasRegistered;

#[EventSourcingAggregate]
class Ticket
{
    use WithAggregateVersioning;

    #[AggregateIdentifier]
    private string $ticketId;
    private string $assignedPerson;
    private string $ticketType;

    #[CommandHandler]
    public static function register(RegisterTicket $command): array
    {
        return [new TicketWasRegistered($command->getTicketId(), $command->getAssignedPerson(), $command->getTicketType())];
    }

    #[CommandHandler]
    public function changeAssignedPerson(ChangeAssignedPerson $command): array
    {
        return [new AssignedPersonWasChanged($command->getTicketId(), $command->getAssignedPerson())];
    }

    #[CommandHandler]
    public function close(CloseTicket $command): array
    {
        return [new TicketWasClosed($this->ticketId)];
    }

    #[EventSourcingHandler]
    public function applyTicketWasRegistered(TicketWasRegistered $event): void
    {
        $this->ticketId       = $event->getTicketId();
        $this->assignedPerson = $event->getAssignedPerson();
        $this->ticketType     = $event->getTicketType();
    }

    #[EventSourcingHandler]
    public function applyAssignedPersonWasChanged(AssignedPersonWasChanged $event): void
    {
        $this->assignedPerson = $event->getAssignedPerson();
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }

    public function toArray(): array
    {
        return [
            'ticketId' => $this->ticketId,
            'assignedPerson' => $this->assignedPerson,
            'ticketType' => $this->ticketType,
            'version' => $this->version,
        ];
    }

    public static function fromArray(array $data): Ticket
    {
        $ticket = new self();
        $ticket->ticketId = $data['ticketId'];
        $ticket->assignedPerson = $data['assignedPerson'];
        $ticket->ticketType = $data['ticketType'];
        $ticket->version = $data['version'];

        return $ticket;
    }
}
