<?php


namespace Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Calculating;

use SimplyCodedSoftware\Messaging\Annotation\InboundChannelAdapter;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;

/**
 * Class InboundCalculation
 * @package Fixture\Behat\Calculating
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class InboundCalculation
{
    /**
     * @return int
     * @InboundChannelAdapter(endpointId="inboundCalculator", inputChannelName="calculateChannel")
     * @BeforeMultiplyCalculation(amount=3)
     */
    public function calculateFor() : int
    {
        return 5;
    }
}