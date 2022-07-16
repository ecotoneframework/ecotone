<?php

namespace Test\Ecotone\EventSourcing\Fixture\TicketProjectionState;

final class CounterState
{
    public function __construct(
        public int $ticketCount = 0,
        public int $closedTicketCount = 0
    ) {
    }
}
