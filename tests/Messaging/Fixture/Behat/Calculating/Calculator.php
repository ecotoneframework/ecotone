<?php
declare(strict_types=1);


namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;

interface Calculator
{
    /**
     * @param int $amount
     * @return int
     * @BeforeMultiplyCalculation(amount=2)
     * @AroundSumCalculation(amount=3)
     * @AfterMultiplyCalculation(amount=2)
     */
    #[MessageGateway("calculateChannel")]
    public function calculate(int $amount) : int;
}