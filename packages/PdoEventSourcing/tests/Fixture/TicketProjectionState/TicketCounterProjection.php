<?php

namespace Test\Ecotone\EventSourcing\Fixture\TicketProjectionState;

use Ecotone\EventSourcing\Attribute\Projection;
use Ecotone\EventSourcing\Attribute\ProjectionState;
use Ecotone\EventSourcing\EventStreamEmitter;
use Ecotone\Modelling\Attribute\EventHandler;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Event\TicketWasClosed;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Event\TicketWasRegistered;
use Test\Ecotone\EventSourcing\Fixture\Ticket\Ticket;

#[Projection(self::NAME, Ticket::class)]
class TicketCounterProjection
{
    public const NAME = 'ticketCounter';

    #[EventHandler(endpointId: 'ticketCounter.addTicket')]
    public function whenTicketWasRegistered(TicketWasRegistered $event, #[ProjectionState] array $state, EventStreamEmitter $eventStreamEmitter): array
    {
        if (! isset($state['ticketCount'])) {
            $state['ticketCount'] = 0;
        }

        $state['ticketCount'] += 1;

        $eventStreamEmitter->emit([new TicketCounterChanged($state['ticketCount'])]);

        return $state;
    }

    #[EventHandler(endpointId: 'ticketCounter.closeTicket')]
    public function whenTicketWasClosed(TicketWasClosed $event, #[ProjectionState] CounterState $state, EventStreamEmitter $eventStreamEmitter): CounterState
    {
        $state->closedTicketCount += 1;

        $eventStreamEmitter->emit([new ClosedTicketCounterChanged($state->closedTicketCount)]);

        return $state;
    }
}
