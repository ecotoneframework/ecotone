<?php

namespace Messaging\Endpoint;

use Fixture\Handler\DumbMessageHandlerBuilder;
use Fixture\Handler\NoReturnMessageHandler;
use Messaging\Channel\DirectChannel;
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
        $consumerEndpointFactory = new ConsumerEndpointFactory();

        $this->assertInstanceOf(EventDrivenConsumer::class,
            $consumerEndpointFactory->create(
                DumbMessageHandlerBuilder::create(
            "some",
                new NoReturnMessageHandler(),
                DirectChannel::create()
            ))
        );
    }

    public function future_test_creating_pollable_consumer()
    {

    }
}