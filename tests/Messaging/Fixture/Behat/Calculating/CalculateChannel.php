<?php


namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Extension;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;

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