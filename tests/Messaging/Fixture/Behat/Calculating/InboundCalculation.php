<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\Scheduled;

/**
 * licence Apache-2.0
 */
class InboundCalculation
{
    #[Scheduled('calculateForInbound', 'inboundCalculator')]
    #[BeforeMultiplyCalculation(3)]
    #[AfterMultiplyCalculation(10)]
    #[AroundSumCalculation(2)]
    public function calculateFor(): int
    {
        return 5;
    }

    #[InternalHandler('calculateForInbound', outputChannelName: 'resultChannel')]
    public function calculate(int $number): int
    {
        return $number;
    }
}
