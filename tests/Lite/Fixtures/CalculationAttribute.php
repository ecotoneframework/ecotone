<?php

namespace Test\Ecotone\Lite\Fixtures;

use Attribute;

#[Attribute]
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
