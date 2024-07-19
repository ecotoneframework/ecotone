<?php

namespace Test\Ecotone\Modelling\Fixture\TwoSagas;

/**
 * licence Apache-2.0
 */
class OrderWasPlaced
{
    public function __construct(private string $orderId)
    {
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }
}
