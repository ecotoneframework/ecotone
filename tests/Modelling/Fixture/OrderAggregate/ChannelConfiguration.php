<?php

namespace Test\Ecotone\Modelling\Fixture\OrderAggregate;

use Ecotone\Api\Attribute\ServiceContext;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;

/**
 * licence Apache-2.0
 */
class ChannelConfiguration
{
    public const ERROR_CHANNEL = 'errorChannel';

    #[ServiceContext]
    public function registerAsyncChannel(): array
    {
        return [
            SimpleMessageChannelBuilder::createQueueChannel('orders'),
            PollingMetadata::create('orders')
                ->withExecutionTimeLimitInMilliseconds(1)
                ->withHandledMessageLimit(1)
                ->withErrorChannelName(self::ERROR_CHANNEL),
        ];
    }
}
