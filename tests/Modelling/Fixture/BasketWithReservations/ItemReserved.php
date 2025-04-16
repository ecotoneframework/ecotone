<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\BasketWithReservations;

final class ItemReserved
{
    public function __construct(public string $itemId)
    {
    }
}
