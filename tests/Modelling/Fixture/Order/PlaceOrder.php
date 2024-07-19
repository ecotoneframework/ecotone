<?php

namespace Test\Ecotone\Modelling\Fixture\Order;

/**
 * licence Apache-2.0
 */
class PlaceOrder
{
    public function __construct(private string $orderId)
    {
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }
}
