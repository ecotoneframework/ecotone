<?php

namespace Test\Ecotone\Amqp\Fixture\Shop;

use Ecotone\Amqp\AmqpQueue;
use Ecotone\Amqp\Configuration\AmqpMessageConsumerConfiguration;
use Ecotone\Amqp\Publisher\AmqpMessagePublisherConfiguration;
use Ecotone\Messaging\Attribute\ServiceContext;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\MessagePublisher;

class MessagingConfiguration
{
    public const CONSUMER_ID    = 'addToCart';
    public const SHOPPING_QUEUE = 'shopping';

    #[ServiceContext]
    public function registerPublisher()
    {
        return AmqpMessagePublisherConfiguration::create(MessagePublisher::class)
            ->withAutoDeclareQueueOnSend(true)
            ->withDefaultRoutingKey(self::SHOPPING_QUEUE);
    }

    #[ServiceContext]
    public function registerConsumer()
    {
        return [
            AmqpQueue::createWith(self::SHOPPING_QUEUE),
            AmqpMessageConsumerConfiguration::create(self::CONSUMER_ID, self::SHOPPING_QUEUE)
                ->withReceiveTimeoutInMilliseconds(1),
            PollingMetadata::create(self::CONSUMER_ID)
                ->setExecutionTimeLimitInMilliseconds(1),
        ];
    }
}
