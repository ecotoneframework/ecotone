<?php

namespace Messaging\Config;

use Fixture\Handler\DumbMessageHandlerBuilder;
use Fixture\Handler\NoReturnMessageHandler;
use Messaging\Channel\DirectChannel;
use Messaging\Channel\MessageDispatchingException;
use Messaging\Channel\QueueChannel;
use Messaging\Channel\SimpleMessageChannelBuilder;
use Messaging\Endpoint\EventDrivenConsumer;
use Messaging\Endpoint\PollOrThrowExceptionConsumer;
use Messaging\Endpoint\PollOrThrowPollableConsumerFactory;
use Messaging\MessagingTest;
use Messaging\Support\InvalidArgumentException;
use Messaging\Support\MessageBuilder;

/**
 * Class ApplicationTest
 * @package Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessagingSystemConfigurationTest extends MessagingTest
{
    public function test_run_event_driven_consumer()
    {
        $subscribableChannelName = "input";
        $subscribableChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();

        $messagingSystem = MessagingSystemConfiguration::prepare()
            ->registerMessageHandler(DumbMessageHandlerBuilder::create('test', $messageHandler, $subscribableChannelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($subscribableChannelName, $subscribableChannel))
            ->setPollableFactory(new PollOrThrowPollableConsumerFactory())
            ->buildMessagingSystemFromConfiguration();

        $messagingSystem->runEventDrivenConsumers();
        $subscribableChannel->send(MessageBuilder::withPayload("a")->build());

        $this->assertTrue($messageHandler->wasCalled());
    }

    public function test_not_running_event_driven_if_stopped()
    {
        $subscribableChannelName = "input";
        $subscribableChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();

        $messagingSystem = MessagingSystemConfiguration::prepare()
            ->registerMessageHandler(DumbMessageHandlerBuilder::create('test', $messageHandler, $subscribableChannelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($subscribableChannelName, $subscribableChannel))
            ->setPollableFactory(new PollOrThrowPollableConsumerFactory())
            ->buildMessagingSystemFromConfiguration();

        $messagingSystem->runEventDrivenConsumers();
        $messagingSystem->stopEventDrivenConsumers();

        $this->expectException(MessageDispatchingException::class);

        $subscribableChannel->send(MessageBuilder::withPayload("a")->build());
    }

    public function test_not_running_pollable_consumers_for_event_driven()
    {
        $pollableChannelName = "pollable";
        $pollableChannel = QueueChannel::create();
        $messageHandler = NoReturnMessageHandler::create();

        $messagingSystem = MessagingSystemConfiguration::prepare()
            ->registerMessageHandler(DumbMessageHandlerBuilder::create('test', $messageHandler, $pollableChannelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($pollableChannelName, $pollableChannel))
            ->setPollableFactory(new PollOrThrowPollableConsumerFactory())
            ->buildMessagingSystemFromConfiguration();

        $messagingSystem->runEventDrivenConsumers();

        $message = MessageBuilder::withPayload("a")->build();
        $pollableChannel->send($message);

        $this->assertMessages($message, $pollableChannel->receive());
    }

    public function test_running_pollable_consumer()
    {
        $messageChannelName = "pollableChannel";
        $pollableChannel = QueueChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $pollableName = 'test';

        $messagingSystem = MessagingSystemConfiguration::prepare()
            ->registerMessageHandler(DumbMessageHandlerBuilder::create($pollableName, $messageHandler, $messageChannelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($messageChannelName, $pollableChannel))
            ->setPollableFactory(new PollOrThrowPollableConsumerFactory())
            ->buildMessagingSystemFromConfiguration();

        $message = MessageBuilder::withPayload("a")->build();
        $pollableChannel->send($message);

        $messagingSystem->runPollableByName($pollableName);

        $this->assertTrue($messageHandler->wasCalled());
    }

    public function test_throwing_exception_if_running_event_driven_as_pollable()
    {
        $subscribableChannelName = "input";
        $messageHandler = NoReturnMessageHandler::create();

        $messageHandlerName = 'test';
        $messagingSystem = MessagingSystemConfiguration::prepare()
            ->registerMessageHandler(DumbMessageHandlerBuilder::create($messageHandlerName, $messageHandler, $subscribableChannelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($subscribableChannelName, DirectChannel::create()))
            ->setPollableFactory(new PollOrThrowPollableConsumerFactory())
            ->buildMessagingSystemFromConfiguration();

        $this->expectException(InvalidArgumentException::class);

        $messagingSystem->runPollableByName($messageHandlerName);
    }
}