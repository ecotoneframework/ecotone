<?php

namespace Test\Ecotone\Lite\Fixtures;

interface Operation
{
    public function calculate(int $amount): int;
}
