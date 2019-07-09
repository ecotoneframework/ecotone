<?php


namespace Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Calculating;

use SimplyCodedSoftware\Messaging\Annotation\ApplicationContext;
use SimplyCodedSoftware\Messaging\Annotation\Extension;
use SimplyCodedSoftware\Messaging\Channel\SimpleMessageChannelBuilder;

/**
 * Class CalculateChannel
 * @package Fixture\Behat\Calculating
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ApplicationContext()
 */
class CalculateChannel
{
    /**
     * @return array
     * @Extension()
     */
    public function configuration() : array
    {
        return [
            SimpleMessageChannelBuilder::createQueueChannel("resultChannel")
        ];
    }
}