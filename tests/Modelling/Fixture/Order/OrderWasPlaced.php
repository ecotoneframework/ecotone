<?php

namespace Test\Ecotone\Modelling\Fixture\Order;

class OrderWasPlaced
{
    /**
     * @var string
     */
    private $orderId;

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }
}