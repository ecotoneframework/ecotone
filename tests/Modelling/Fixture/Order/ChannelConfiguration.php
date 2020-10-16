<?php


namespace Test\Ecotone\Modelling\Fixture\Order;

use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;

class ChannelConfiguration
{
    /**
     * @ApplicationContext()
     */
    public function registerAsyncChannel() : array
    {
        return [
            SimpleMessageChannelBuilder::createQueueChannel("orders"),
            PollingMetadata::create("orders")
                ->setExecutionTimeLimitInMilliseconds(1)
                ->setHandledMessageLimit(1)
        ];
    }
}