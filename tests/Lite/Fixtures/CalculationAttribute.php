<?php

namespace Test\Ecotone\Lite\Fixtures;

use Attribute;

#[Attribute]
/**
 * licence Apache-2.0
 */
class CalculationAttribute
{
    public function __construct(private Operation $calculation)
    {
    }

    public function apply(int $amount): int
    {
        return $this->calculation->calculate($amount);
    }
}
