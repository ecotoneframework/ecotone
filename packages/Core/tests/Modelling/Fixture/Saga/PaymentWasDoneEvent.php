<?php


namespace Test\Ecotone\Modelling\Fixture\Saga;


class PaymentWasDoneEvent
{
    private function __construct()
    {
    }

    public static function create() : self
    {
        return new self();
    }
}