<?php

namespace Test\Ecotone\Modelling\Fixture\MetadataPropagatingForMultipleEndpoints;

use Ecotone\Api\Attribute\ServiceContext;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;

/**
 * licence Apache-2.0
 */
class MessagingConfiguration
{
    #[ServiceContext]
    public function asyncChannel()
    {
        return [
            SimpleMessageChannelBuilder::createQueueChannel('notifications'),
            PollingMetadata::create('notifications')
                ->withHandledMessageLimit(1)
                ->withExecutionTimeLimitInMilliseconds(1),
        ];
    }
}
