<?php


namespace Test\Ecotone\Modelling\Fixture\MetadataPropagatingForMultipleEndpoints;

use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;

class MessagingConfiguration
{
    #[ApplicationContext]
    public function asyncChannel()
    {
        return [
            SimpleMessageChannelBuilder::createQueueChannel("notifications"),
            PollingMetadata::create("notifications")
                ->setHandledMessageLimit(1)
                ->setExecutionTimeLimitInMilliseconds(1)
        ];
    }
}