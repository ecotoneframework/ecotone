<?php

namespace Test\Ecotone\Modelling\Fixture\Ticket;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
/**
 * licence Apache-2.0
 */
class Ticket
{
    use WithAggregateVersioning;

    #[Identifier]
    private $ticketId;
    private ?string $workerId = null;

    #[CommandHandler]
    public static function start(StartTicketCommand $command): array
    {
        return [new TicketWasStartedEvent($command->getTicketId())];
    }

    #[Apply]
    public function onTicketWasStarted(TicketWasStartedEvent $event): void
    {
        $this->ticketId = $event->getTicketId();
    }

    #[Apply]
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

    public function id(): mixed
    {
        return $this->ticketId;
    }

    public function getAggregateVersion(): int
    {
        return $this->version;
    }

    public function getWorkerId(): ?string
    {
        return $this->workerId;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }
}
