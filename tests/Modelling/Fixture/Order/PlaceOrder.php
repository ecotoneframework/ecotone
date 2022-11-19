<?php

namespace Test\Ecotone\Modelling\Fixture\Order;

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
