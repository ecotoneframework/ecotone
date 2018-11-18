<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Config;

use Fixture\Annotation\MessageEndpoint\ServiceActivator\WithPoller\ServiceActivatorWithPollerExample;
use Fixture\Annotation\ModuleConfiguration\ExampleModuleConfiguration;
use Fixture\Channel\DumbChannelInterceptor;
use Fixture\Configuration\FakeModule;
use Fixture\Handler\DumbGatewayBuilder;
use Fixture\Handler\DumbMessageHandlerBuilder;
use Fixture\Handler\ExceptionMessageHandler;
use Fixture\Handler\ModuleMessageHandlerBuilder;
use Fixture\Handler\NoReturnMessageHandler;
use Fixture\Service\CalculatingService;
use Fixture\Service\ServiceWithoutReturnValue;
use SimplyCodedSoftware\IntegrationMessaging\Channel\ChannelInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelAdapter;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleChannelInterceptorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryModuleMessaging;
use SimplyCodedSoftware\IntegrationMessaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\OrderedMethodInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\EventDriven\EventDrivenConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollOrThrow\PollOrThrowMessageHandlerConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Processor\MethodInvoker\ReferenceBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;
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
    /**
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_run_event_driven_consumer()
    {
        $subscribableChannelName = "input";
        $subscribableChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();

        $this->createMessagingSystemConfiguration()
            ->registerMessageHandler(DumbMessageHandlerBuilder::create($messageHandler, $subscribableChannelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($subscribableChannelName, $subscribableChannel))
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $subscribableChannel->send(MessageBuilder::withPayload("a")->build());

        $this->assertTrue($messageHandler->wasCalled());
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_registering_module_with_extension_objects()
    {
        $exampleModuleConfiguration = ExampleModuleConfiguration::createEmpty();
        MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createWith([$exampleModuleConfiguration], [new \stdClass(), ServiceWithoutReturnValue::create()]));

        $this->assertEquals(
            ExampleModuleConfiguration::createWithExtensions([new \stdClass()]),
            $exampleModuleConfiguration
        );
    }

    /**
     * @return MessagingSystemConfiguration
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function createMessagingSystemConfiguration(): MessagingSystemConfiguration
    {
        return MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_running_pollable_consumer()
    {
        $messageChannelName = "pollableChannel";
        $pollableChannel = QueueChannel::create();
        $messageHandler = NoReturnMessageHandler::create();

        $messagingSystem = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler(DumbMessageHandlerBuilder::create($messageHandler, $messageChannelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($messageChannelName, $pollableChannel))
            ->registerConsumerFactory(new PollOrThrowMessageHandlerConsumerBuilder())
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $message = MessageBuilder::withPayload("a")->build();
        $pollableChannel->send($message);

        $this->expectException(InvalidArgumentException::class);

        $messagingSystem->runSeparatelyRunningConsumerBy($messagingSystem->getListOfSeparatelyRunningConsumers()[0]);

        $this->assertTrue($messageHandler->wasCalled());
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_throwing_exception_if_running_not_existing_consumer()
    {
        $messagingSystem = $this->createMessagingSystemConfiguration()
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $this->expectException(InvalidArgumentException::class);

        $messagingSystem->runSeparatelyRunningConsumerBy("some");
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_notifying_observer()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());

        $messagingSystemConfiguration
            ->registerMessageHandler(DumbMessageHandlerBuilder::create(NoReturnMessageHandler::create(), 'queue'))
            ->registerGatewayBuilder(DumbGatewayBuilder::create()->withRequiredReference("some"))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create("queue", QueueChannel::create()))
            ->registerConsumerFactory(new PollOrThrowMessageHandlerConsumerBuilder())
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create("queue", "interceptor"))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith(["interceptor" => new DumbChannelInterceptor()]));

        $this->assertEquals(["dumb" => \stdClass::class], $messagingSystemConfiguration->getRegisteredGateways());
        $this->assertEquals([NoReturnMessageHandler::class, "some", "interceptor"], $messagingSystemConfiguration->getRequiredReferences());
    }

    /**
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_serializing_and_deserializing()
    {
        $config = MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createWith([], []))
            ->registerMessageHandler(DumbMessageHandlerBuilder::create(NoReturnMessageHandler::create(), 'queue'))
            ->registerGatewayBuilder(DumbGatewayBuilder::create()->withRequiredReference("some"))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create("queue", QueueChannel::create()))
            ->registerConsumerFactory(new PollOrThrowMessageHandlerConsumerBuilder())
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create("queue", "interceptor"));

        $this->assertEquals(
            $config,
            unserialize(serialize($config))
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_informing_exposing_required_references()
    {
        $messagingSystem = MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());

        $messagingSystem->registerMessageHandler(
            ServiceActivatorBuilder::create("ref-a", "method-a")
                ->withInputChannelName("someChannel")
                ->withMethodParameterConverters([
                    ReferenceBuilder::create("some", "ref-b")
                ])
        );

        $this->assertEquals(
            ["ref-a", "ref-b"],
            $messagingSystem->getRequiredReferences()
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_intercepting_message_flow_before_sending()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());

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

    /**
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_ordering_channel_interceptors_before_sending()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());

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

    /**
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_intercepting_by_stopping_message_flow()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());

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

    /**
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_intercepting_after_sending_to_inform_it_was_successful()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());

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

    /**
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_intercepting_after_sending_to_inform_about_failure_handling_after_exception_occurred()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());

        $messageChannelName = "requestChannel";
        $referenceName = "ref-name";
        $messagingSystemConfiguration
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel($messageChannelName))
            ->registerMessageHandler(DumbMessageHandlerBuilder::create(ExceptionMessageHandler::create(), $messageChannelName))
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
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

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_intercepting_with_multiple_channels()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());

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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_register_message_handler_with_fake_module()
    {
        $fakeModule = FakeModule::create();
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createWith([$fakeModule], []));

        $messageHandlerBuilder = ModuleMessageHandlerBuilder::create("fake", "fake");
        $messagingSystemConfiguration->registerMessageHandler($messageHandlerBuilder);
        $messagingSystemConfiguration->registerConsumerFactory(new EventDrivenConsumerBuilder());
        $messagingSystemConfiguration->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel("fake"));
        $messagingSystemConfiguration->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $this->assertEquals($fakeModule, $messageHandlerBuilder->getModule());
    }

    /**
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_registering_channel_interceptor_with_regex()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());

        $messageChannelName = "requestChannel";
        $referenceName = "ref-name";
        $messagingSystemConfiguration
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($messageChannelName))
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create("request*", $referenceName));

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

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_creating_implicit_direct_channel_if_not_exists()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());

        $inputMessageChannelName = "inputChannelName";
        $messageHandler = NoReturnMessageHandler::create();
        $messagingSystem = $messagingSystemConfiguration
            ->registerMessageHandler(DumbMessageHandlerBuilder::create($messageHandler, $inputMessageChannelName))
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->buildMessagingSystemFromConfiguration(
                InMemoryReferenceSearchService::createEmpty()
            );

        $messagingSystem->getMessageChannelByName($inputMessageChannelName)
            ->send(MessageBuilder::withPayload("some")->build());

        $this->assertTrue($messageHandler->wasCalled());
    }

    /**
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_replacing_implicit_direct_channel_with_real_channel_if_passed()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());

        $inputMessageChannelName = "inputChannelName";
        $messageHandler = NoReturnMessageHandler::create();
        $messagingSystem = $messagingSystemConfiguration
            ->registerMessageHandler(DumbMessageHandlerBuilder::create($messageHandler, $inputMessageChannelName))
            ->registerConsumerFactory(new PollOrThrowMessageHandlerConsumerBuilder())
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($inputMessageChannelName))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $messagingSystem->getMessageChannelByName($inputMessageChannelName)
            ->send(MessageBuilder::withPayload("some")->build());

        $this->assertFalse($messageHandler->wasCalled(), "Queue channel was registered, so without explicit calling the consumer it should not be ever called");
    }

    /**
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_registering_polling_consumer_with_metadata()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());

        $inputMessageChannelName = "inputChannelName";
        $messageHandler = ExceptionMessageHandler::create();
        $endpointName = "pollableName";
        $messagingSystem = $messagingSystemConfiguration
            ->registerMessageHandler(
                DumbMessageHandlerBuilder::create($messageHandler, $inputMessageChannelName)
                    ->withEndpointId($endpointName)
            )
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($inputMessageChannelName))
            ->registerPollingMetadata(PollingMetadata::create($endpointName))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $messagingSystem->getMessageChannelByName($inputMessageChannelName)
            ->send(MessageBuilder::withPayload("some")->build());

        $this->expectException(\InvalidArgumentException::class);

        $messagingSystem->runSeparatelyRunningConsumerBy($endpointName);
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_registering_pre_method_call_interceptor()
    {
        $endpointName = "endpointName";
        $inputChannelName = "inputChannel";
        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty())
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), "sum")
                        ->withInputChannelName($inputChannelName)
                        ->withEndpointId($endpointName)
                )
                ->registerPreCallMethodInterceptor(
                    OrderedMethodInterceptor::create(
                        ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "sum")
                            ->withEndpointId($endpointName),
                    OrderedMethodInterceptor::DEFAULT_ORDER_WEIGHT
                    )
                )
                ->registerPostCallMethodInterceptor(
                    OrderedMethodInterceptor::create(
                        ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(3), "multiply")
                            ->withEndpointId($endpointName),
                        OrderedMethodInterceptor::DEFAULT_ORDER_WEIGHT
                    )
                )
                ->registerConsumerFactory(new EventDrivenConsumerBuilder())
                ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $messageChannel = $messagingSystemConfiguration->getMessageChannelByName($inputChannelName);
        $outputChannel = QueueChannel::create();

        $messageChannel->send(
            MessageBuilder::withPayload(5)
                ->setReplyChannel($outputChannel)
                ->build()
        );

        $this->assertEquals(
            21,
            $outputChannel->receive()->getPayload()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_registering_with_weight_orders()
    {
        $endpointName = "endpointName";
        $inputChannelName = "inputChannel";
        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty())
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), "sum")
                        ->withInputChannelName($inputChannelName)
                        ->withEndpointId($endpointName)
                )
                ->registerPreCallMethodInterceptor(
                    OrderedMethodInterceptor::create(
                        ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(3), "multiply")
                            ->withEndpointId($endpointName),
                        3
                    )
                )
                ->registerPreCallMethodInterceptor(
                    OrderedMethodInterceptor::create(
                        ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "sum")
                            ->withEndpointId($endpointName),
                        1
                    )
                )
                ->registerPostCallMethodInterceptor(
                    OrderedMethodInterceptor::create(
                        ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "multiply")
                            ->withEndpointId($endpointName),
                        3
                    )
                )
                ->registerPostCallMethodInterceptor(
                    OrderedMethodInterceptor::create(
                        ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "sum")
                            ->withEndpointId($endpointName),
                        1
                    )
                )
                ->registerConsumerFactory(new EventDrivenConsumerBuilder())
                ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $messageChannel = $messagingSystemConfiguration->getMessageChannelByName($inputChannelName);
        $outputChannel = QueueChannel::create();

        $messageChannel->send(
            MessageBuilder::withPayload(2)
                ->setReplyChannel($outputChannel)
                ->build()
        );

        $this->assertEquals(
            18,
            $outputChannel->receive()->getPayload()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_throwing_exception_if_passed_pre_call_interceptor_without_endpoint_name()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty())
            ->registerPreCallMethodInterceptor(
                OrderedMethodInterceptor::create(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(3), "multiply"),
                    OrderedMethodInterceptor::DEFAULT_ORDER_WEIGHT
                )
            );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_throwing_exception_if_passed_post_call_interceptor_without_endpoint_name()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty())
            ->registerPostCallMethodInterceptor(
                OrderedMethodInterceptor::create(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(3), "multiply"),
                    OrderedMethodInterceptor::DEFAULT_ORDER_WEIGHT
                )
            );
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_throwing_exception_if_registering_handlers_with_same_endpoint_id()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty())
            ->registerMessageHandler(DumbMessageHandlerBuilder::createSimple()->withEndpointId("1"))
            ->registerMessageHandler(DumbMessageHandlerBuilder::createSimple()->withEndpointId("1"));
    }

    /**
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_generating_random_id_if_no_endpoint_id_passed()
    {
        MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty())
            ->registerMessageHandler(DumbMessageHandlerBuilder::createSimple())
            ->registerMessageHandler(DumbMessageHandlerBuilder::createSimple());

        $this->assertTrue(true);
    }

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_throwing_exception_if_trying_to_register_two_channels_with_same_names()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty())
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel("some"))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel("some"));
    }
}