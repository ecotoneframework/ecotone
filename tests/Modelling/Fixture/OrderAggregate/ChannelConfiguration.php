<?php

namespace Test\Ecotone\Modelling\Fixture\OrderAggregate;

use Ecotone\Messaging\Annotation\ServiceContext;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;

class ChannelConfiguration
{
    const ERROR_CHANNEL = "errorChannel";

    #[ServiceContext]
    public function registerAsyncChannel(): array
    {
        return [
            SimpleMessageChannelBuilder::createQueueChannel("orders"),
            PollingMetadata::create("orders")
                ->setExecutionTimeLimitInMilliseconds(1)
                ->setHandledMessageLimit(1)
                ->setErrorChannelName(self::ERROR_CHANNEL)
        ];
    }
}