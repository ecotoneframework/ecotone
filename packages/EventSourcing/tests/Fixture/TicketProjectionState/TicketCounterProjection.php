<?php


namespace Test\Ecotone\EventSourcing\Fixture\TicketProjectionState;

use Doctrine\DBAL\Connection;
use Ecotone\EventSourcing\Attribute\Projection;
use Ecotone\EventSourcing\Attribute\ProjectionDelete;
use Ecotone\EventSourcing\Attribute\ProjectionInitialization;
use Ecotone\EventSourcing\Attribute\ProjectionReset;
use Ecotone\EventSourcing\Attribute\ProjectionState;
use Ecotone\EventSourcing\EventStore;
use Ecotone\EventSourcing\EventStreamEmitter;
use Ecotone\EventSourcing\LazyProophProjectionManager;
use Ecotone\EventSourcing\ProjectionManager;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Event\TicketWasClosed;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Event\TicketWasRegistered;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Ticket;

#[Projection(self::NAME, Ticket::class)]
class TicketCounterProjection
{
    const NAME = "ticketCounter";

    #[EventHandler(endpointId: "ticketCounter.addTicket")]
    public function whenTicketWasRegistered(TicketWasRegistered $event, #[ProjectionState] array $state, EventStreamEmitter $eventStreamEmitter): array
    {
        if (!isset($state["ticketCount"])) {
            $state["ticketCount"] = 0;
        }

        $state["ticketCount"] += 1;

        $eventStreamEmitter->emit([new TicketCounterChanged($state['ticketCount'])]);

        return $state;
    }

    #[EventHandler(endpointId: "ticketCounter.closeTicket")]
    public function whenTicketWasClosed(TicketWasClosed $event, #[ProjectionState] CounterState $state, EventStreamEmitter $eventStreamEmitter): CounterState
    {
        $state->closedTicketCount += 1;

        $eventStreamEmitter->emit([new ClosedTicketCounterChanged($state->closedTicketCount)]);

        return $state;
    }
}