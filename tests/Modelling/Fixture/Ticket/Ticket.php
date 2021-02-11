<?php


namespace Test\Ecotone\Modelling\Fixture\Ticket;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateFactory;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\AggregateVersion;
use Ecotone\Modelling\Attribute\EventSourcedAggregate;
use Ecotone\Modelling\WithVersioning;

#[EventSourcedAggregate]
class Ticket
{
    use WithVersioning;

    #[AggregateIdentifier]
    private $ticketId;
    /**
     * @var string
     */
    private $workerId;

    #[CommandHandler]
    public static function start(StartTicketCommand $command): array
    {
        return [new TicketWasStartedEvent($command->getTicketId())];
    }

    #[AggregateFactory]
    public static function createFrom(array $events): self
    {
        $self = new self();

        foreach ($events as $event) {
            switch (get_class($event)) {
                case TicketWasStartedEvent::class:
                    {
                        $self->applyTicketWasStarted($event);
                        break;
                    }
                case WorkerWasAssignedEvent::class:
                    {
                        $self->applyWorkerWasAssigned($event);
                        break;
                    }
            }
        }

        return $self;
    }

    private function applyTicketWasStarted(TicketWasStartedEvent $event): void
    {
        $this->ticketId = $event->getTicketId();
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
        $this->applyWorkerWasAssigned($event);

        return [$event];
    }

    private function applyWorkerWasAssigned(WorkerWasAssignedEvent $event): void
    {
        $this->workerId = $event->getAssignedWorkerId();
    }

    public function getVersion(): int
    {
        return $this->version;
    }
}