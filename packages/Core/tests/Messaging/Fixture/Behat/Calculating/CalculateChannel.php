<?php


namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Messaging\Attribute\ServiceContext;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;

class CalculateChannel
{
    #[ServiceContext]
    public function configuration(): array
    {
        return [
            SimpleMessageChannelBuilder::createQueueChannel("resultChannel")
        ];
    }
}