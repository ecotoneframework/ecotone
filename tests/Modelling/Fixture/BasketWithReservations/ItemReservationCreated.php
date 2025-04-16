<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\BasketWithReservations;

final class ItemReservationCreated
{
    public function __construct(public string $itemId)
    {
    }
}
