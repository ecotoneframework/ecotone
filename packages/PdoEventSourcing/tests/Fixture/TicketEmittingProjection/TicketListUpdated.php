<?php

namespace Test\Ecotone\EventSourcing\Fixture\TicketEmittingProjection;

final class TicketListUpdated
{
    public function __construct(public string $ticketId)
    {
    }
}
