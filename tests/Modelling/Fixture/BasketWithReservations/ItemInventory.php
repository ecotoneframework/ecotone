<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\BasketWithReservations;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
final class ItemInventory
{
    use WithAggregateVersioning;

    #[Identifier]
    private string $itemId;

    #[EventHandler(endpointId: 'item.itemReservationCreated')]
    #[Asynchronous(channelName: 'itemInventory')]
    public function whenItemReservationCreated(ItemReservationCreated $event): array
    {
        return [new ItemReserved($event->itemId)];
    }

    #[EventSourcingHandler]
    public function applyItemInventoryCreated(ItemInventoryCreated $event): void
    {
        $this->itemId = $event->itemId;
    }
}
