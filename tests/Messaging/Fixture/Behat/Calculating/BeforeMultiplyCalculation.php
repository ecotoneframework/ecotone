<?php


namespace Tests\Ecotone\Messaging\Fixture\Behat\Calculating;

#[\Attribute]
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