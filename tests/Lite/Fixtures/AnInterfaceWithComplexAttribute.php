<?php

namespace Test\Ecotone\Lite\Fixtures;

/**
 * licence Apache-2.0
 */
interface AnInterfaceWithComplexAttribute
{
    #[AroundCalculation(new Sum(3))]
    public function calculate(int $amount): int;
}
