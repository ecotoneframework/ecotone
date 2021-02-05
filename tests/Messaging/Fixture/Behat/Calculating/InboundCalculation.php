<?php


namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Messaging\Attribute\Scheduled;
use Ecotone\Messaging\Attribute\ServiceActivator;

class InboundCalculation
{
    #[Scheduled("calculateForInbound", "inboundCalculator")]
    #[BeforeMultiplyCalculation(3)]
    #[AfterMultiplyCalculation(10)]
    #[AroundSumCalculation(2)]
    public function calculateFor() : int
    {
        return 5;
    }

    #[ServiceActivator("calculateForInbound", outputChannelName: "resultChannel")]
    public function calculate(int $number) : int
    {
        return $number;
    }
}