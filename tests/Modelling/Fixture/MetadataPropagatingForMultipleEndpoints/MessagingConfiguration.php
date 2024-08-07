<?php

namespace Test\Ecotone\Modelling\Fixture\MetadataPropagatingForMultipleEndpoints;

use Ecotone\Messaging\Attribute\ServiceContext;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;

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
                ->setHandledMessageLimit(1)
                ->setExecutionTimeLimitInMilliseconds(1),
        ];
    }
}
