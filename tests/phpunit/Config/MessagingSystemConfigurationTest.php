<?php

namespace Test\SimplyCodedSoftware\Messaging\Config;

use Fixture\Configuration\DumbConfigurationObserver;
use Fixture\Handler\DumbGatewayBuilder;
use Fixture\Handler\DumbMessageHandlerBuilder;
use Fixture\Handler\NoReturnMessageHandler;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Channel\MessageDispatchingException;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Config\InMemoryModuleMessagingConfiguration;
use SimplyCodedSoftware\Messaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\Messaging\Endpoint\EventDrivenMessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\Messaging\Endpoint\PollOrThrowMessageHandlerConsumerBuilderFactory;
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

        $this->createMessagingSystemConfiguration()
            ->registerMessageHandler(DumbMessageHandlerBuilder::create('test', $messageHandler, $subscribableChannelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($subscribableChannelName, $subscribableChannel))
            ->registerConsumerFactory(new EventDrivenMessageHandlerConsumerBuilderFactory())
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $subscribableChannel->send(MessageBuilder::withPayload("a")->build());

        $this->assertTrue($messageHandler->wasCalled());
    }

    public function test_running_pollable_consumer()
    {
        $messageChannelName = "pollableChannel";
        $pollableChannel = QueueChannel::create();
        $messageHandler = NoReturnMessageHandler::create();
        $pollableName = 'test';

        $messagingSystem = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler(DumbMessageHandlerBuilder::create($pollableName, $messageHandler, $messageChannelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($messageChannelName, $pollableChannel))
            ->registerConsumerFactory(new PollOrThrowMessageHandlerConsumerBuilderFactory())
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $message = MessageBuilder::withPayload("a")->build();
        $pollableChannel->send($message);

        $messagingSystem->runSeparatelyRunningConsumerBy($pollableName);

        $this->assertTrue($messageHandler->wasCalled());
    }

    public function test_throwing_exception_if_running_event_driven_as_pollable()
    {
        $subscribableChannelName = "input";
        $messageHandler = NoReturnMessageHandler::create();

        $messageHandlerName = 'test';
        $messagingSystem = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler(DumbMessageHandlerBuilder::create($messageHandlerName, $messageHandler, $subscribableChannelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($subscribableChannelName, DirectChannel::create()))
            ->registerConsumerFactory(new EventDrivenMessageHandlerConsumerBuilderFactory())
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $this->expectException(InvalidArgumentException::class);

        $messagingSystem->runSeparatelyRunningConsumerBy($messageHandlerName);
    }

    public function test_notifying_observer()
    {
        $dumbConfigurationObserver = DumbConfigurationObserver::create();
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessagingConfiguration::createEmpty(), $dumbConfigurationObserver);

        $messagingSystemConfiguration
            ->registerMessageHandler(DumbMessageHandlerBuilder::create('some', NoReturnMessageHandler::create(), 'queue'))
            ->registerGatewayBuilder(DumbGatewayBuilder::create())
            ->registerMessageChannel(SimpleMessageChannelBuilder::create("queue", QueueChannel::create()))
            ->registerConsumerFactory(new PollOrThrowMessageHandlerConsumerBuilderFactory())
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $this->assertTrue($dumbConfigurationObserver->wasNotifiedCorrectly(), "Configuration observer was not notified correctly");
    }

    /**
     * @return MessagingSystemConfiguration
     */
    private function createMessagingSystemConfiguration(): MessagingSystemConfiguration
    {
        return MessagingSystemConfiguration::prepare(InMemoryModuleMessagingConfiguration::createEmpty(), DumbConfigurationObserver::create());
    }
}