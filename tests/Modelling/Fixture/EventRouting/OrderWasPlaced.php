<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\EventRouting;

/**
 * licence Apache-2.0
 */
final class OrderWasPlaced
{
    public function __construct(public string $orderId)
    {
    }
}
