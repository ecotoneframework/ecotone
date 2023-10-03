<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Attribute;

#[Attribute]
class PowerCalculation
{
    public function __construct(private int $power)
    {
    }
}
