<?php
declare(strict_types=1);


namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Messaging\Annotation\MessageGateway;
use Ecotone\Messaging\Annotation\MessageEndpoint;

/**
 * Interface Calculator
 * @package Fixture\Behat\Calculating
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface Calculator
{
    /**
     * @MessageGateway(requestChannel="calculateChannel")
     * @param int $amount
     * @return int
     * @BeforeMultiplyCalculation(amount=2)
     * @AroundSumCalculation(amount=3)
     * @AfterMultiplyCalculation(amount=2)
     */
    public function calculate(int $amount) : int;
}