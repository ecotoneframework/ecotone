<?php

namespace Test\Ecotone\Lite\Fixtures;

interface AnInterfaceWithComplexAttribute
{
    #[AroundCalculation(new Sum(3))]
    public function calculate(int $amount): int;
}
