<?php

namespace Test\SimplyCodedSoftware\Messaging\Config;

use Fixture\Handler\DumbMessageHandlerBuilder;
use Fixture\Handler\NoReturnMessageHandler;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Channel\MessageDispatchingException;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Config\InMemoryModuleMessagingConfiguration;
use SimplyCodedSoftware\Messaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\Messaging\Endpoint\EventDrivenConsumerFactory;
use SimplyCodedSoftware\Messaging\Endpoint\PollOrThrowConsumerFactory;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\MessagingTest;

/**
 * Class ApplicationTest
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessagingSystemConfigurationTest extends MessagingTest
{
    public function test_run_event_driven_consumer()
    {
        $subscribableChannelName = "input";
        $subscribableChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();

        MessagingSystemConfiguration::prepare(InMemoryReferenceSearchService::createEmpty(), InMemoryModuleMessagingConfiguration::createEmpty())
            ->registerMessageHandler(DumbMessageHandlerBuilder::create('test', $messageHandler, $subscribableChannelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($subscribableChannelName, $subscribableChannel))
            ->registerConsumerFactory(new EventDrivenConsumerFactory())
            ->buildMessagingSystemFromConfiguration();

        $subscribableChannel->send(MessageBuilder::withPayload("a")->build());

        $this->assertTrue($messageHandler->wasCalled());
    }

    public function test_running_pollable_consumer()
    {
        $messageChannelName = "pollableChannel";
        $pollableChannel = QueueChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $pollableName = 'test';

        $messagingSystem = MessagingSystemConfiguration::prepare(InMemoryReferenceSearchService::createEmpty(), InMemoryModuleMessagingConfiguration::createEmpty())
            ->registerMessageHandler(DumbMessageHandlerBuilder::create($pollableName, $messageHandler, $messageChannelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($messageChannelName, $pollableChannel))
            ->registerConsumerFactory(new PollOrThrowConsumerFactory())
            ->buildMessagingSystemFromConfiguration();

        $message = MessageBuilder::withPayload("a")->build();
        $pollableChannel->send($message);

        $messagingSystem->runConsumerByName($pollableName);

        $this->assertTrue($messageHandler->wasCalled());
    }

    public function test_throwing_exception_if_running_event_driven_as_pollable()
    {
        $subscribableChannelName = "input";
        $messageHandler = NoReturnMessageHandler::create();

        $messageHandlerName = 'test';
        $messagingSystem = MessagingSystemConfiguration::prepare(InMemoryReferenceSearchService::createEmpty(), InMemoryModuleMessagingConfiguration::createEmpty())
            ->registerMessageHandler(DumbMessageHandlerBuilder::create($messageHandlerName, $messageHandler, $subscribableChannelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($subscribableChannelName, DirectChannel::create()))
            ->registerConsumerFactory(new EventDrivenConsumerFactory())
            ->buildMessagingSystemFromConfiguration();

        $this->expectException(InvalidArgumentException::class);

        $messagingSystem->runConsumerByName($messageHandlerName);
    }
}