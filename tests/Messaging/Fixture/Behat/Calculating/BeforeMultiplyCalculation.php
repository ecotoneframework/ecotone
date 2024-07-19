<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Attribute;

#[Attribute]
/**
 * licence Apache-2.0
 */
class BeforeMultiplyCalculation
{
    /**
     * @var integer
     */
    public $amount;

    public function __construct(int $amount)
    {
        $this->amount = $amount;
    }
}
