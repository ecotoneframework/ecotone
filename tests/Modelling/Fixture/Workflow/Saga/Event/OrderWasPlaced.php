<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Workflow\Saga\Event;

final class OrderWasPlaced
{
    public function __construct(
        public string $orderId
    ) {
    }
}
