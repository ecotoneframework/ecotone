<?php

namespace Test\SimplyCodedSoftware\Messaging\Unit\Endpoint;

use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\NoReturnMessageHandler;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Channel\MessageDispatchingException;
use SimplyCodedSoftware\Messaging\Endpoint\EventDriven\EventDrivenConsumer;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;

/**
 * Class EventDrivenConsumerTest
 * @package SimplyCodedSoftware\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EventDrivenConsumerTest extends MessagingTest
{
    public function test_starting_consumer()
    {
        $directChannel = DirectChannel::create();
        $handler = NoReturnMessageHandler::create();
        $eventDrivenConsumer = new EventDrivenConsumer('some', $directChannel, $handler);

        $eventDrivenConsumer->run();

        $directChannel->send(MessageBuilder::withPayload('test')->build());

        $this->assertTrue($handler->wasCalled(), "Handler for event driven consumer was not called");
    }

    public function test_stopping_consumer()
    {
        $directChannel = DirectChannel::create();
        $handler = NoReturnMessageHandler::create();
        $eventDrivenConsumer = new EventDrivenConsumer('some', $directChannel, $handler);

        $eventDrivenConsumer->run();
        $eventDrivenConsumer->stop();

        $this->expectException(MessageDispatchingException::class);

        $directChannel->send(MessageBuilder::withPayload('test')->build());
    }

    public function test_naming_and_configuration()
    {
        $directChannel = DirectChannel::create();
        $handler = NoReturnMessageHandler::create();
        $eventDrivenConsumer = new \SimplyCodedSoftware\Messaging\Endpoint\EventDriven\EventDrivenConsumer('some', $directChannel, $handler);

        $this->assertEquals("some", $eventDrivenConsumer->getConsumerName());
    }
}