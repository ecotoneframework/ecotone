<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Attribute;

#[Attribute]
class AfterMultiplyCalculation
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
