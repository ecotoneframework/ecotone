<?php

namespace Test\Ecotone\EventSourcing\Fixture\TicketProjectionState;

use Ecotone\Messaging\Attribute\Converter;

final class StateAndEventConverter
{
    #[Converter]
    public function fromState(CounterState $state): array
    {
        return [
            "ticketCount" => $state->ticketCount,
            "closedTicketCount" => $state->closedTicketCount
        ];
    }

    #[Converter]
    public function toState(array $state): CounterState
    {
        return new CounterState($state["ticketCount"], $state["closedTicketCount"] ?? 0);
    }

    #[Converter]
    public function fromTicketCounterChanged(TicketCounterChanged $event): array
    {
        return [
            "count" => $event->count
        ];
    }

    #[Converter]
    public function toTicketCounterChanged(array $event): TicketCounterChanged
    {
        return new TicketCounterChanged($event['count']);
    }

    #[Converter]
    public function fromClosedTicketCounterChanged(ClosedTicketCounterChanged $event): array
    {
        return [
            "count" => $event->count
        ];
    }

    #[Converter]
    public function toClosedTicketCounterChanged(array $event): ClosedTicketCounterChanged
    {
        return new ClosedTicketCounterChanged($event['count']);
    }
}