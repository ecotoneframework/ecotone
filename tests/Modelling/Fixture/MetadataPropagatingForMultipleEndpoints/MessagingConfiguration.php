<?php


namespace Test\Ecotone\Modelling\Fixture\MetadataPropagatingForMultipleEndpoints;

use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Extension;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;

/**
 * @ApplicationContext()
 */
class MessagingConfiguration
{
    /**
     * @Extension()
     */
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