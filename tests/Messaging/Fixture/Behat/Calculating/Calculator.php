<?php
declare(strict_types=1);


namespace Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Calculating;

use SimplyCodedSoftware\Messaging\Annotation\Gateway;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;

/**
 * Interface Calculator
 * @package Fixture\Behat\Calculating
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
interface Calculator
{
    /**
     * @Gateway(requestChannel="calculateChannel")
     * @param int $amount
     * @return int
     * @BeforeMultiplyCalculation(amount=2)
     * @AroundMultiplyCalculation()
     * @AfterSumCalculation(amount=3)
     */
    public function calculate(int $amount) : int;
}