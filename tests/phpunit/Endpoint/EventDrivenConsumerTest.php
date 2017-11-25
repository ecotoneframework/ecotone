<?php

namespace Messaging\Endpoint;

use Fixture\Handler\NoReturnMessageHandler;
use Messaging\Channel\DirectChannel;
use Messaging\Channel\MessageDispatchingException;
use Messaging\MessageDeliveryException;
use Messaging\MessagingTest;
use Messaging\Support\MessageBuilder;

/**
 * Class EventDrivenConsumerTest
 * @package Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EventDrivenConsumerTest extends MessagingTest
{
    public function test_starting_consumer()
    {
        $directChannel = DirectChannel::create();
        $handler = NoReturnMessageHandler::create();
        $eventDrivenConsumer = new EventDrivenConsumer('some', $directChannel, $handler);

        $eventDrivenConsumer->start();

        $directChannel->send(MessageBuilder::withPayload('test')->build());

        $this->assertTrue($handler->wasCalled(), "Handler for event driven consumer was not called");
        $this->assertTrue($eventDrivenConsumer->isRunning(), "Event driven consumer should be running");
    }

    public function test_stopping_consumer()
    {
        $directChannel = DirectChannel::create();
        $handler = NoReturnMessageHandler::create();
        $eventDrivenConsumer = new EventDrivenConsumer('some', $directChannel, $handler);

        $eventDrivenConsumer->start();
        $eventDrivenConsumer->stop();

        $this->expectException(MessageDispatchingException::class);

        $directChannel->send(MessageBuilder::withPayload('test')->build());
        $this->assertFalse($eventDrivenConsumer->isRunning(), "Event driven consumer should not be running after");
    }

    public function test_naming_and_configuration()
    {
        $directChannel = DirectChannel::create();
        $handler = NoReturnMessageHandler::create();
        $eventDrivenConsumer = new EventDrivenConsumer('some', $directChannel, $handler);

        $this->assertEquals("some", $eventDrivenConsumer->getConsumerName());
        $this->assertEquals("", $eventDrivenConsumer->getMissingConfiguration());
        $this->assertFalse($eventDrivenConsumer->isMissingConfiguration(), "Configuration should not be missing");
    }
}