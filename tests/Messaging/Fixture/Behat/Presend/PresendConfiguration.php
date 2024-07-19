<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Presend;

use Ecotone\Messaging\Attribute\ServiceContext;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\PollingMetadata;

/**
 * licence Apache-2.0
 */
class PresendConfiguration
{
    #[ServiceContext]
    public function shopBuyConfiguration()
    {
        return [
            SimpleMessageChannelBuilder::createQueueChannel('shop', conversionMediaType: MediaType::createApplicationXPHP()),
            PollingMetadata::create('shop')
                ->setExecutionAmountLimit(1)
                ->setHandledMessageLimit(1),
        ];
    }
}
