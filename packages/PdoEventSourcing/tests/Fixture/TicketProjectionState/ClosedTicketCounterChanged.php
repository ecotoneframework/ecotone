<?php

namespace Test\Ecotone\EventSourcing\Fixture\TicketProjectionState;

final class ClosedTicketCounterChanged
{
    public function __construct(public int $count)
    {
    }
}
