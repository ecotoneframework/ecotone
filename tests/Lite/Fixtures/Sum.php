<?php

namespace Test\Ecotone\Lite\Fixtures;

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
