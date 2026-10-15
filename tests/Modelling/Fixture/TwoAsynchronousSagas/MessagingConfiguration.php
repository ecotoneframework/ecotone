<?php

namespace Test\Ecotone\Modelling\Fixture\TwoAsynchronousSagas;

use Ecotone\Api\Attribute\ServiceContext;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;

/**
 * licence Apache-2.0
 */
class MessagingConfiguration
{
    public const ASYNCHRONOUS_CHANNEL = 'asynchronous_channel';

    #[ServiceContext]
    public function polling()
    {
        return [
            PollingMetadata::create(self::ASYNCHRONOUS_CHANNEL)
                ->withTestingSetup(),
        ];
    }

    #[ServiceContext]
    public function asynchronous()
    {
        return SimpleMessageChannelBuilder::createQueueChannel(self::ASYNCHRONOUS_CHANNEL);
    }
}
