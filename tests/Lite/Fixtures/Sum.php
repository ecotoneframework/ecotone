<?php

namespace Test\Ecotone\Lite\Fixtures;

/**
 * licence Apache-2.0
 */
class Sum implements Operation
{
    public function __construct(
        private int $amount
    ) {
    }

    public function calculate(int $amount): int
    {
        return $amount + $this->amount;
    }
}
