<?php

namespace Test\Ecotone\EventSourcing\Fixture\TicketProjectionState;

use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

final class NotificationService
{
    private int $currentTicketCount = 0;
    private int $closedTicketCount = 0;

    #[QueryHandler("ticket.getCurrentCount")]
    public function getCurrentCounter(): int
    {
        return $this->currentTicketCount;
    }

    #[QueryHandler("ticket.getClosedCount")]
    public function getClosedCounter(): int
    {
        return $this->closedTicketCount;
    }

    #[EventHandler]
    public function whenTicketCounterChanged(TicketCounterChanged $event): void
    {
        $this->currentTicketCount = $event->count;
    }

    #[EventHandler]
    public function whenClosedTicketCounterChanged(ClosedTicketCounterChanged $event): void
    {
        $this->closedTicketCount = $event->count;
    }
}