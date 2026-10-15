<?php

namespace Test\Ecotone\Modelling\Fixture\Order;

use Ecotone\Api\Attribute\Environment;
use Ecotone\Api\Attribute\ServiceContext;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;

/**
 * licence Apache-2.0
 */
class ChannelConfiguration
{
    #[ServiceContext]
    #[Environment(['dev', 'prod'])]
    public function registerAsyncChannel(): array
    {
        return [
            SimpleMessageChannelBuilder::createQueueChannel('orders'),
            PollingMetadata::create('orders')
                ->withExecutionTimeLimitInMilliseconds(1)
                ->withHandledMessageLimit(1)
                ->withStopOnError(true),
        ];
    }
}
