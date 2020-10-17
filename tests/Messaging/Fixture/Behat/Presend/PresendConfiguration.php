<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Presend;

use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;

class PresendConfiguration
{
    #[ApplicationContext]
    public function shopBuyConfiguration()
    {
        return [
            SimpleMessageChannelBuilder::createQueueChannel("shop"),
            PollingMetadata::create("shop")
                ->setExecutionAmountLimit(1)
                ->setHandledMessageLimit(1)
        ];
    }
}