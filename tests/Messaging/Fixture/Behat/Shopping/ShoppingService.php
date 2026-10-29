<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Shopping;

use Ecotone\Messaging\Attribute\MessageGateway;

/**
 * licence Apache-2.0
 */
interface ShoppingService
{
    #[MessageGateway('reserveRequest')]
    public function reserve(string $productName): BookWasReserved;
}
