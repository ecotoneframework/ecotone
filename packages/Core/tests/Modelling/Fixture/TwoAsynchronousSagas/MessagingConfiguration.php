<?php

namespace Test\Ecotone\Modelling\Fixture\TwoAsynchronousSagas;

use Ecotone\Messaging\Attribute\ServiceContext;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;

class MessagingConfiguration
{
    const ASYNCHRONOUS_CHANNEL = "asynchronous_channel";

    #[ServiceContext]
    public function polling()
    {
        return [
            PollingMetadata::create(self::ASYNCHRONOUS_CHANNEL)
                ->withTestingSetup()
        ];
    }

    #[ServiceContext]
    public function asynchronous()
    {
        return SimpleMessageChannelBuilder::createQueueChannel(self::ASYNCHRONOUS_CHANNEL);
    }
}