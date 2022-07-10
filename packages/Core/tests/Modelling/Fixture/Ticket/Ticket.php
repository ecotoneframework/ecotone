<?php


namespace Test\Ecotone\Modelling\Fixture\Ticket;

use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
class Ticket
{
    use WithAggregateVersioning;

    #[AggregateIdentifier]
    private $ticketId;
    private ?string $workerId = null;

    #[CommandHandler]
    public static function start(StartTicketCommand $command): array
    {
        return [new TicketWasStartedEvent($command->getTicketId())];
    }

    #[EventSourcingHandler]
    public function onTicketWasStarted(TicketWasStartedEvent $event): void
    {
        $this->ticketId = $event->getTicketId();
    }

    #[EventSourcingHandler]
    public function onWorkerWasAssigned(WorkerWasAssignedEvent $event): void
    {
        $this->workerId = $event->getAssignedWorkerId();
    }

    #[CommandHandler]
    public function assignWorker(AssignWorkerCommand $command): array
    {
        if ($this->workerId) {
            if ($command->getWorkerId() === $this->workerId) {
                return [];
            }

            return [new WorkerAssignationFailedEvent($this->ticketId, $command->getWorkerId())];
        }

        $event = new WorkerWasAssignedEvent($this->ticketId, $command->getWorkerId());

        return [$event];
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }
}