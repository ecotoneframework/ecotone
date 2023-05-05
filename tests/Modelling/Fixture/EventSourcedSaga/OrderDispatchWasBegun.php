<?php

namespace Test\Ecotone\Modelling\Fixture\EventSourcedSaga;

class OrderDispatchWasBegun
{
    public function __construct(private string $orderId)
    {
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }
}
