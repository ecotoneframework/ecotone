<?php

namespace Test\Ecotone\Modelling\Fixture\Saga;

/**
 * licence Apache-2.0
 */
class PaymentWasDoneEvent
{
    private function __construct(public readonly int $paymentId)
    {
    }

    public static function create(int $paymentId): self
    {
        return new self($paymentId);
    }
}
