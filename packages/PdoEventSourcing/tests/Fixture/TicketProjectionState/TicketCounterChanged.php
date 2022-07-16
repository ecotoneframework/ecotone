<?php

namespace Test\Ecotone\EventSourcing\Fixture\TicketProjectionState;

final class TicketCounterChanged
{
    public function __construct(public int $count)
    {
    }
}
