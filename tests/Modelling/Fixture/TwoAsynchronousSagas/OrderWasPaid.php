<?php

namespace Test\Ecotone\Modelling\Fixture\TwoAsynchronousSagas;

/**
 * licence Apache-2.0
 */
class OrderWasPaid
{
    public function __construct(private string $orderId)
    {
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }
}
