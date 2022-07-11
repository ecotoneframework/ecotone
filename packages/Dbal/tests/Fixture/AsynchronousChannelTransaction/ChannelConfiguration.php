<?php

namespace Test\Ecotone\Dbal\Fixture\AsynchronousChannelTransaction;

use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\Dbal\DbalBackedMessageChannelBuilder;
use Ecotone\Messaging\Attribute\ServiceContext;
use Ecotone\Messaging\Endpoint\PollingMetadata;

class ChannelConfiguration
{
    #[ServiceContext]
    public function registerCommandChannel(): array
    {
        return [
            DbalBackedMessageChannelBuilder::create('orders', 'managerRegistry')
                ->withReceiveTimeout(1),
            PollingMetadata::create('orders')
                ->setHandledMessageLimit(1)
                ->setExecutionTimeLimitInMilliseconds(1),
            DbalBackedMessageChannelBuilder::create('processOrders', 'managerRegistry')
                ->withReceiveTimeout(1),
            PollingMetadata::create('processOrders')
                ->setHandledMessageLimit(1)
                ->setExecutionTimeLimitInMilliseconds(1),
            DbalConfiguration::createWithDefaults()
                ->withTransactionOnAsynchronousEndpoints(true)
                ->withTransactionOnCommandBus(true)
                ->withDefaultConnectionReferenceNames(['managerRegistry'])
                ->withDocumentStore(false)
                ->withDeduplication(false)
                ->withDeadLetter(false),
        ];
    }
}
