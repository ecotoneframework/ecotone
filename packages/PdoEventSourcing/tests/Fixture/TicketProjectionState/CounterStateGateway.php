<?php

namespace Test\Ecotone\EventSourcing\Fixture\TicketProjectionState;

use Ecotone\EventSourcing\Attribute\ProjectionStateGateway;

interface CounterStateGateway
{
    #[ProjectionStateGateway(TicketCounterProjection::NAME)]
    public function fetchState(): CounterState;
}
