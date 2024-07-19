<?php

namespace Test\Ecotone\Modelling\Fixture\EventSourcedSaga;

/**
 * licence Apache-2.0
 */
class OrderDispatchWasFinished
{
    public function __construct(private string $orderId)
    {
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }
}
