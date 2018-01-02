<?php

namespace Messaging\Endpoint;

use Fixture\Handler\DumbChannelResolver;
use Fixture\Handler\DumbMessageHandlerBuilder;
use Fixture\Handler\NoReturnMessageHandler;
use Messaging\Channel\DirectChannel;
use Messaging\Config\InMemoryChannelResolver;
use Messaging\MessagingTest;

/**
 * Class ConsumerEndpointFactoryTest
 * @package Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConsumerEndpointFactoryTest extends MessagingTest
{
    public function test_creating_event_driven_consumer()
    {
        $consumerEndpointFactory = new ConsumerEndpointFactory(InMemoryChannelResolver::createEmpty(), new PollOrThrowPollableConsumerFactory());

        $this->assertInstanceOf(EventDrivenConsumer::class,
            $consumerEndpointFactory->create(
                DumbMessageHandlerBuilder::create(
            "some",
                new NoReturnMessageHandler(),
                DirectChannel::create()
            ))
        );
    }
}