<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\BasketWithReservations;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Modelling\WithAggregateVersioning;

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

    #[Apply]
    public function applyBasketCreated(BasketCreated $event): void
    {
        $this->basketId = $event->basketId;
    }
}
