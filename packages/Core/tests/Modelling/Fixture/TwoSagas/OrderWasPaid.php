<?php


namespace Test\Ecotone\Modelling\Fixture\TwoSagas;


class OrderWasPaid
{
    public function __construct(private string $orderId) {}

    public function getOrderId(): string
    {
        return $this->orderId;
    }
}