<?php


namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;

class CalculateChannel
{
    /**
     * @return array
     * @ApplicationContext()
     */
    public function configuration(): array
    {
        return [
            SimpleMessageChannelBuilder::createQueueChannel("resultChannel")
        ];
    }
}