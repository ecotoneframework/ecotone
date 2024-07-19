<?php

namespace Test\Ecotone\Lite\Fixtures;

/**
 * licence Apache-2.0
 */
interface Operation
{
    public function calculate(int $amount): int;
}
