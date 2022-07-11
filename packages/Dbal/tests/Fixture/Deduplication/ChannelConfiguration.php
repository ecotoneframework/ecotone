<?php

namespace Test\Ecotone\Dbal\Fixture\AsynchronousChannelTransaction;

use Ecotone\Dbal\DbalBackedMessageChannelBuilder;
use Ecotone\Messaging\Attribute\ServiceContext;
use Ecotone\Messaging\Endpoint\PollingMetadata;

class ChannelConfiguration
{
    #[ServiceContext]
    public function registerCommandChannel(): array
    {
        return [
            DbalBackedMessageChannelBuilder::create('processOrders')
                ->withReceiveTimeout(1),
            PollingMetadata::create('processOrders')
                ->setHandledMessageLimit(1)
                ->setExecutionTimeLimitInMilliseconds(1),
        ];
    }
}
