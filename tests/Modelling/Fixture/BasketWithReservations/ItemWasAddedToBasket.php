<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\BasketWithReservations;

final class ItemWasAddedToBasket
{
    public function __construct(public string $basketId, public string $itemId)
    {
    }
}
