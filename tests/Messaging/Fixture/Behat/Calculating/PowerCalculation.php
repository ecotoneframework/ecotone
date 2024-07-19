<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Attribute;

#[Attribute]
/**
 * licence Apache-2.0
 */
class PowerCalculation
{
    public function __construct(private int $power)
    {
    }
}
