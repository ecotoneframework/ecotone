<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\BasketWithReservations;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
final class Basket
{
    use WithAggregateVersioning;

    #[Identifier]
    private string $basketId;

    #[CommandHandler]
    public function addItem(AddItemToBasket $command): array
    {
        return [new ItemWasAddedToBasket($this->basketId, $command->itemId)];
    }

    #[EventHandler(endpointId: 'basket.itemWasAddedToBasket')]
    #[Asynchronous(channelName: 'basket')]
    public function whenItemWasAddedToBasket(ItemWasAddedToBasket $event): array
    {
        return [new ItemReservationCreated($event->itemId)];
    }

    #[EventSourcingHandler]
    public function applyBasketCreated(BasketCreated $event): void
    {
        $this->basketId = $event->basketId;
    }
}
