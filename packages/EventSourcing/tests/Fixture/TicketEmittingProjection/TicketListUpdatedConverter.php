<?php

namespace Test\Ecotone\EventSourcing\Fixture\TicketEmittingProjection;

use Ecotone\Messaging\Attribute\Converter;

final class TicketListUpdatedConverter
{
    #[Converter]
    public function toArray(TicketListUpdated $event): array
    {
        return [
            "ticketId" => $event->ticketId
        ];
    }

    #[Converter]
    public function fromArray(array $event): TicketListUpdated
    {
        return new TicketListUpdated($event['ticketId']);
    }
}