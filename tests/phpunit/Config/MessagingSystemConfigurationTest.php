<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config;

use Fixture\Channel\DumbChannelInterceptor;
use Fixture\Configuration\DumbConfigurationObserver;
use Fixture\Handler\DumbGatewayBuilder;
use Fixture\Handler\DumbMessageHandlerBuilder;
use Fixture\Handler\ExceptionMessageHandler;
use Fixture\Handler\NoReturnMessageHandler;
use SimplyCodedSoftware\IntegrationMessaging\Channel\ChannelInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelAdapter;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageDispatchingException;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleChannelInterceptorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryModuleMessagingConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\EventDrivenMessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollOrThrowMessageHandlerConsumerBuilderFactory;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class ApplicationTest
 * @package SimplyCodedSoftware\IntegrationMessaging\Config
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
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessagingConfiguration::createEmpty(), InMemoryConfigurationVariableRetrievingService::createEmpty(), $dumbConfigurationObserver);

        $messagingSystemConfiguration
            ->registerMessageHandler(DumbMessageHandlerBuilder::create('some', NoReturnMessageHandler::create(), 'queue'))
            ->registerGatewayBuilder(DumbGatewayBuilder::create())
            ->registerMessageChannel(SimpleMessageChannelBuilder::create("queue", QueueChannel::create()))
            ->registerConsumerFactory(new PollOrThrowMessageHandlerConsumerBuilderFactory())
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $this->assertTrue($dumbConfigurationObserver->wasNotifiedCorrectly(), "Configuration observer was not notified correctly");
        $this->assertEquals([NoReturnMessageHandler::class], $dumbConfigurationObserver->getRequiredReferences());
    }

    public function test_intercepting_message_flow_before_sending()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessagingConfiguration::createEmpty(), InMemoryConfigurationVariableRetrievingService::createEmpty(), DumbConfigurationObserver::create());

        $messageChannelName = "requestChannel";
        $referenceName = "ref-name";
        $messagingSystemConfiguration
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($messageChannelName))
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create($messageChannelName, $referenceName));

        $channelInterceptor = $this->createMock(ChannelInterceptor::class);
        $messagingSystem = $messagingSystemConfiguration->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
            $referenceName => $channelInterceptor
        ]));

        $message = MessageBuilder::withPayload("testMessage")->build();
        /** @var QueueChannel|MessageChannelAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName);

        $preSendModifiedMessage = MessageBuilder::withPayload("preSend")->build();
        $channelInterceptor->method("preSend")
            ->with($message, $queueChannel->getInternalMessageChannel())
            ->willReturn($preSendModifiedMessage);

        $queueChannel->send($message);

        $this->assertEquals(
            $preSendModifiedMessage,
            $queueChannel->receive()
        );
    }

    public function test_ordering_interception_before_sending()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessagingConfiguration::createEmpty(), InMemoryConfigurationVariableRetrievingService::createEmpty(), DumbConfigurationObserver::create());

        $messageChannelName = "requestChannel";
        $referenceNameSecondToCall = "interceptor-1";
        $referenceNameFirstToCall = "interceptor-2";
        $messagingSystemConfiguration
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($messageChannelName))
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create($messageChannelName, $referenceNameSecondToCall)->withImportance(1))
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create($messageChannelName, $referenceNameFirstToCall)->withImportance(2));

        $channelInterceptorSecondToCall = $this->createMock(ChannelInterceptor::class);
        $channelInterceptorFirstToCall = $this->createMock(ChannelInterceptor::class);
        $messagingSystem = $messagingSystemConfiguration->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
            $referenceNameSecondToCall => $channelInterceptorSecondToCall,
            $referenceNameFirstToCall => $channelInterceptorFirstToCall
        ]));

        /** @var QueueChannel|MessageChannelAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName);

        $message = MessageBuilder::withPayload("testMessage")->build();
        $messageFirstModification = MessageBuilder::withPayload("preSend1")->build();
        $messageSecondModification = MessageBuilder::withPayload("preSend2")->build();

        $channelInterceptorFirstToCall->method("preSend")
            ->with($message, $queueChannel->getInternalMessageChannel())
            ->willReturn($messageFirstModification);
        $channelInterceptorSecondToCall->method("preSend")
            ->with($messageFirstModification, $queueChannel->getInternalMessageChannel())
            ->willReturn($messageSecondModification);

        $queueChannel->send($message);

        $this->assertEquals(
            $messageSecondModification,
            $queueChannel->receive()
        );
    }

    public function test_intercepting_by_stopping_message_flow()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessagingConfiguration::createEmpty(), InMemoryConfigurationVariableRetrievingService::createEmpty(), DumbConfigurationObserver::create());

        $messageChannelName = "requestChannel";
        $referenceName = "ref-name";
        $messagingSystemConfiguration
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($messageChannelName))
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create($messageChannelName, $referenceName));

        $channelInterceptor = $this->createMock(ChannelInterceptor::class);
        $messagingSystem = $messagingSystemConfiguration->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
            $referenceName => $channelInterceptor
        ]));

        $message = MessageBuilder::withPayload("testMessage")->build();
        /** @var QueueChannel|MessageChannelAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName);

        $channelInterceptor->method("preSend")
            ->with($message, $queueChannel->getInternalMessageChannel())
            ->willReturn(null);

        $queueChannel->send($message);

        $this->assertEquals(
            null,
            $queueChannel->receive()
        );
    }

    public function test_intercepting_after_sending_to_inform_it_was_successful()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessagingConfiguration::createEmpty(), InMemoryConfigurationVariableRetrievingService::createEmpty(), DumbConfigurationObserver::create());

        $messageChannelName = "requestChannel";
        $referenceName = "ref-name";
        $messagingSystemConfiguration
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($messageChannelName))
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create($messageChannelName, $referenceName));

        $channelInterceptor = $this->createMock(ChannelInterceptor::class);
        $messagingSystem = $messagingSystemConfiguration->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
            $referenceName => $channelInterceptor
        ]));

        $message = MessageBuilder::withPayload("testMessage")->build();
        /** @var QueueChannel|MessageChannelAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName);

        $channelInterceptor->method("preSend")
            ->with($message, $queueChannel->getInternalMessageChannel())
            ->willReturn($message);

        $channelInterceptor
            ->expects($this->once())
            ->method("postSend")
            ->with($message, $queueChannel->getInternalMessageChannel(), true);

        $queueChannel->send($message);
    }

    public function test_intercepting_after_sending_to_inform_about_failure_handling_after_exception_occurred()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessagingConfiguration::createEmpty(), InMemoryConfigurationVariableRetrievingService::createEmpty(), DumbConfigurationObserver::create());

        $messageChannelName = "requestChannel";
        $referenceName = "ref-name";
        $messagingSystemConfiguration
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel($messageChannelName))
            ->registerMessageHandler(DumbMessageHandlerBuilder::create("some", ExceptionMessageHandler::create(), $messageChannelName))
            ->registerConsumerFactory(new EventDrivenMessageHandlerConsumerBuilderFactory())
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create($messageChannelName, $referenceName));

        $channelInterceptor = $this->createMock(ChannelInterceptor::class);
        $messagingSystem = $messagingSystemConfiguration->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
            $referenceName => $channelInterceptor
        ]));

        $message = MessageBuilder::withPayload("testMessage")->build();
        /** @var QueueChannel|MessageChannelAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName);

        $channelInterceptor->method("preSend")
            ->with($message, $queueChannel->getInternalMessageChannel())
            ->willReturn($message);

        $this->expectException(\InvalidArgumentException::class);

        $channelInterceptor
            ->expects($this->once())
            ->method("postSend")
            ->with($message, $queueChannel->getInternalMessageChannel(), false);

        $queueChannel->send($message);
    }

    public function test_intercepting_with_multiple_channels()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessagingConfiguration::createEmpty(), InMemoryConfigurationVariableRetrievingService::createEmpty(), DumbConfigurationObserver::create());

        $messageChannelName1 = "requestChannel1";
        $messageChannelName2 = "requestChannel2";
        $referenceName1 = "ref-name1";
        $referenceName2 = "ref-name2";
        $messagingSystemConfiguration
            ->registerMessageChannel(SimpleMessageChannelBuilder::createPublishSubscribeChannel($messageChannelName1))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($messageChannelName2))
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create($messageChannelName1, $referenceName1))
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create($messageChannelName2, $referenceName2));

        $channelInterceptor1 = $this->createMock(ChannelInterceptor::class);
        $channelInterceptor2 = $this->createMock(ChannelInterceptor::class);
        $messagingSystem = $messagingSystemConfiguration->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
            $referenceName1 => $channelInterceptor1,
            $referenceName2 => $channelInterceptor2
        ]));

        $message = MessageBuilder::withPayload("testMessage")->build();
        /** @var QueueChannel|MessageChannelAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName2);

        $preSendModifiedMessage = MessageBuilder::withPayload("preSend")->build();
        $channelInterceptor2->method("preSend")
            ->with($message, $queueChannel->getInternalMessageChannel())
            ->willReturn($preSendModifiedMessage);

        $queueChannel->send($message);

        $this->assertEquals(
            $preSendModifiedMessage,
            $queueChannel->receive()
        );
    }

    /**
     * @return MessagingSystemConfiguration
     */
    private function createMessagingSystemConfiguration(): MessagingSystemConfiguration
    {
        return MessagingSystemConfiguration::prepare(InMemoryModuleMessagingConfiguration::createEmpty(), InMemoryConfigurationVariableRetrievingService::createEmpty(), DumbConfigurationObserver::create());
    }
}