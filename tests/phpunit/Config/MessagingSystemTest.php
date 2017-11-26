<?php

namespace Messaging\Config;

use Fixture\Handler\NoReturnMessageHandler;
use Messaging\Channel\DirectChannel;
use Messaging\Channel\MessageDispatchingException;
use Messaging\Channel\QueueChannel;
use Messaging\Endpoint\EventDrivenConsumer;
use Messaging\Endpoint\PollOrThrowExceptionConsumer;
use Messaging\MessagingTest;
use Messaging\Support\InvalidArgumentException;
use Messaging\Support\MessageBuilder;

/**
 * Class ApplicationTest
 * @package Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessagingSystemTest extends MessagingTest
{
    public function test_run_event_driven_consumer()
    {
        $subscribableChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $eventDrivenConsumer = new EventDrivenConsumer('consumer-a', $subscribableChannel, $messageHandler);
        $messagingSystem = MessagingSystem::create([$eventDrivenConsumer]);

        $messagingSystem->runEventDrivenConsumers();
        $subscribableChannel->send(MessageBuilder::withPayload("a")->build());

        $this->assertTrue($messageHandler->wasCalled());
    }

    public function test_not_running_event_driven_if_stopped()
    {
        $subscribableChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $eventDrivenConsumer = new EventDrivenConsumer('consumer-a', $subscribableChannel, $messageHandler);
        $messagingSystem = MessagingSystem::create([$eventDrivenConsumer]);

        $messagingSystem->runEventDrivenConsumers();
        $messagingSystem->stopEventDrivenConsumers();

        $this->expectException(MessageDispatchingException::class);

        $subscribableChannel->send(MessageBuilder::withPayload("a")->build());
    }

    public function test_not_running_pollable_consumers_for_event_driven()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $pollableChannel = QueueChannel::create();
        $pollableConsumer = PollOrThrowExceptionConsumer::createWithoutName($pollableChannel, $messageHandler);
        $messagingSystem = MessagingSystem::create([$pollableConsumer]);

        $messagingSystem->runEventDrivenConsumers();

        $message = MessageBuilder::withPayload("a")->build();
        $pollableChannel->send($message);

        $this->assertMessages($message, $pollableChannel->receive());
    }

    public function test_running_pollable_consumer()
    {
        $messageHandler = NoReturnMessageHandler::create();
        $pollableChannel = QueueChannel::create();
        $consumerName = 'poller';
        $pollableConsumer = PollOrThrowExceptionConsumer::create($consumerName, $pollableChannel, $messageHandler);
        $messagingSystem = MessagingSystem::create([$pollableConsumer]);

        $message = MessageBuilder::withPayload("a")->build();
        $pollableChannel->send($message);

        $messagingSystem->runPollableByName($consumerName);

        $this->assertTrue($messageHandler->wasCalled());
    }

    public function test_throwing_exception_if_running_event_driven_as_pollable()
    {
        $subscribableChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $consumerName = 'consumer-a';
        $eventDrivenConsumer = new EventDrivenConsumer($consumerName, $subscribableChannel, $messageHandler);
        $messagingSystem = MessagingSystem::create([$eventDrivenConsumer]);

        $this->expectException(InvalidArgumentException::class);

        $messagingSystem->runPollableByName($consumerName);
    }
}