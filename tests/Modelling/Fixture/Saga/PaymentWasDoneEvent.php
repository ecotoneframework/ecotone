<?php

namespace Test\Ecotone\Modelling\Fixture\Saga;

/**
 * licence Apache-2.0
 */
class PaymentWasDoneEvent
{
    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }
}
