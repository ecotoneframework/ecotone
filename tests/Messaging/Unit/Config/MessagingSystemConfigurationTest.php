<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Config;

use SimplyCodedSoftware\Messaging\Channel\ChannelInterceptor;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Channel\MessageChannelInterceptorAdapter;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Channel\SimpleChannelInterceptorBuilder;
use SimplyCodedSoftware\Messaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Config\ConfigurationException;
use SimplyCodedSoftware\Messaging\Config\InMemoryModuleMessaging;
use SimplyCodedSoftware\Messaging\Config\InMemoryReferenceTypeFromNameResolver;
use SimplyCodedSoftware\Messaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\Messaging\Endpoint\EventDriven\EventDrivenConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Endpoint\PollOrThrow\PollOrThrowMessageHandlerConsumerBuilder;
use SimplyCodedSoftware\Messaging\Handler\Chain\ChainForwarder;
use SimplyCodedSoftware\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\ReferenceBuilder;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use SimplyCodedSoftware\Messaging\Transaction\Transactional;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\Interceptor\CalculatingServiceInterceptorExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\ModuleConfiguration\ExampleModuleConfiguration;
use Test\SimplyCodedSoftware\Messaging\Fixture\Channel\DumbChannelInterceptor;
use Test\SimplyCodedSoftware\Messaging\Fixture\Configuration\FakeModule;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\DumbGatewayBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\DumbMessageHandlerBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\ExceptionMessageHandler;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\NoReturnMessageHandler;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor\TransactionalInterceptorExample;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\CalculatingService;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceWithoutReturnValue;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;

/**
 * Class ApplicationTest
 * @package SimplyCodedSoftware\Messaging\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessagingSystemConfigurationTest extends MessagingTest
{
    /**
     * @throws ConfigurationException
     * @throws \Exception
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function createMessagingSystemConfiguration(): MessagingSystemConfiguration
    {
        return MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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

        $messagingSystem->runSeparatelyRunningConsumerBy($messagingSystem->getListOfSeparatelyRunningConsumers()[0]);

        $this->assertTrue($messageHandler->wasCalled());
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_running_not_existing_consumer()
    {
        $messagingSystem = $this->createMessagingSystemConfiguration()
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $this->expectException(InvalidArgumentException::class);

        $messagingSystem->runSeparatelyRunningConsumerBy("some");
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_registering_required_reference_names()
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

    public function test_registering_reference_names_from_interceptors()
    {
        $messagingSystem = MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());

        $messagingSystem
            ->registerBeforeMethodInterceptor(
                MethodInterceptor::create(
                    "some",
                    ServiceActivatorBuilder::create("reference1", "sum"),
                    MethodInterceptor::DEFAULT_PRECEDENCE,
                    CalculatingService::class
                )
            )
            ->registerAroundMethodInterceptor(
                AroundInterceptorReference::createWithNoPointcut(
                    "reference2", "multiply"
                )
            )
            ->registerAfterMethodInterceptor(
                MethodInterceptor::create(
                    "some",
                    ServiceActivatorBuilder::create("reference3", "multiply"),
                    MethodInterceptor::DEFAULT_PRECEDENCE,
                    CalculatingService::class
                )
            )
        ;

        $this->assertEquals(
            ["reference1", "reference2", "reference3"],
            $messagingSystem->getRequiredReferences()
        );
    }

    public function test_registering_reference_from_endpoint_annotation()
    {
        $messagingSystem = MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());

        $messagingSystem
            ->registerMessageHandler(
                ServiceActivatorBuilder::create("reference1", "sum")
                    ->withInputChannelName("some")
                    ->withEndpointAnnotations([
                        Transactional::createWith(["reference2"])
                    ])
            )
        ;

        $this->assertEquals(
            ["reference1", "reference2"],
            $messagingSystem->getRequiredReferences()
        );
    }

    public function test_registering_reference_from_interface_to_call_on_prepare_method()
    {
        $messagingSystem = MessagingSystemConfiguration::prepareWithCachedReferenceObjects(
            InMemoryModuleMessaging::createWith(
                [
                    ExampleModuleConfiguration::createWithHandlers([
                        ServiceActivatorBuilder::create("reference0", "doAction")
                            ->withInputChannelName("some")
                    ])
                ],
                []
            ),
            InMemoryReferenceTypeFromNameResolver::createFromAssociativeArray([
                "reference0" => TransactionalInterceptorExample::class
            ])
        );

        $this->assertEquals(
            ["reference0", "reference2", "reference1"],
            $messagingSystem->getRequiredReferences()
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
        /** @var QueueChannel|MessageChannelInterceptorAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName);

        $preSendModifiedMessage = MessageBuilder::withPayload("preSend")->build();
        $channelInterceptor->method("preSend")
            ->with($message, $queueChannel->getInternalMessageChannel())
            ->willReturn($preSendModifiedMessage);
        $channelInterceptor->method("preReceive")
            ->willReturn(true);

        $queueChannel->send($message);

        $this->assertEquals(
            $preSendModifiedMessage,
            $queueChannel->receive()
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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

        /** @var QueueChannel|MessageChannelInterceptorAdapter $queueChannel */
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
        $channelInterceptorSecondToCall
            ->method("preReceive")
            ->willReturn(true);
        $channelInterceptorFirstToCall
            ->method("preReceive")
            ->willReturn(true);

        $queueChannel->send($message);

        $this->assertEquals(
            $messageSecondModification,
            $queueChannel->receive()
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
        /** @var QueueChannel|MessageChannelInterceptorAdapter $queueChannel */
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
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
        /** @var QueueChannel|MessageChannelInterceptorAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName);

        $channelInterceptor->method("preSend")
            ->with($message, $queueChannel->getInternalMessageChannel())
            ->willReturn($message);

        $channelInterceptor
            ->expects($this->once())
            ->method("postSend")
            ->with($message, $queueChannel->getInternalMessageChannel());

        $queueChannel->send($message);
    }

    /**
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
        /** @var QueueChannel|MessageChannelInterceptorAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName);

        $channelInterceptor->method("preSend")
            ->with($message, $queueChannel->getInternalMessageChannel())
            ->willReturn($message);

        $this->expectException(\InvalidArgumentException::class);

        $queueChannel->send($message);
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Config\ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
        /** @var QueueChannel|MessageChannelInterceptorAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName2);

        $preSendModifiedMessage = MessageBuilder::withPayload("preSend")->build();
        $channelInterceptor2->method("preSend")
            ->with($message, $queueChannel->getInternalMessageChannel())
            ->willReturn($preSendModifiedMessage);
        $channelInterceptor1
            ->method("preReceive")
            ->willReturn(true);
        $channelInterceptor2
            ->method("preReceive")
            ->willReturn(true);

        $queueChannel->send($message);

        $this->assertEquals(
            $preSendModifiedMessage,
            $queueChannel->receive()
        );
    }

    /**
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
        /** @var QueueChannel|MessageChannelInterceptorAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName);

        $preSendModifiedMessage = MessageBuilder::withPayload("preSend")->build();
        $channelInterceptor->method("preSend")
            ->with($message, $queueChannel->getInternalMessageChannel())
            ->willReturn($preSendModifiedMessage);
        $channelInterceptor
            ->method("preReceive")
            ->willReturn(true);

        $queueChannel->send($message);

        $this->assertEquals(
            $preSendModifiedMessage,
            $queueChannel->receive()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
     * @throws \SimplyCodedSoftware\Messaging\Endpoint\NoConsumerFactoryForBuilderException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_registering_interceptors_using_pointcuts()
    {
        $endpointName = "endpointName";
        $inputChannelName = "inputChannel";
        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty())
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), "sum")
                        ->withInputChannelName($inputChannelName)
                        ->withEndpointId($endpointName)
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptor::create(
                        "some",
                        ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "sum"),
                    MethodInterceptor::DEFAULT_PRECEDENCE,
                        CalculatingService::class
                    )
                )
                ->registerAroundMethodInterceptor(
                    AroundInterceptorReference::createWithDirectObject(
                        CalculatingServiceInterceptorExample::create(2), "sum",
                        1, CalculatingService::class
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptor::create(
                        "some",
                        ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(3), "multiply"),
                        MethodInterceptor::DEFAULT_PRECEDENCE,
                        CalculatingService::class
                    )
                )
                ->registerConsumerFactory(new EventDrivenConsumerBuilder())
                ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $messageChannel = $messagingSystemConfiguration->getMessageChannelByName($inputChannelName);
        $outputChannel = QueueChannel::create();

        $messageChannel->send(
            MessageBuilder::withPayload(0)
                ->setReplyChannel($outputChannel)
                ->build()
        );

        $this->assertEquals(
            15,
            $outputChannel->receive()->getPayload()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_registering_interceptors_by_reference_names()
    {
        $endpointName = "endpointName";
        $inputChannelName = "inputChannel";
        $calculatorWithOne = "calculatorWithOne";
        $calculatorWithTwo = "calculatorWithTwo";
        $calculatorWithTwoAround = "calculatorWithTwoAround";

        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty())
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), "sum")
                        ->withRequiredInterceptorReferenceNames([$calculatorWithTwo, $calculatorWithTwoAround])
                        ->withInputChannelName($inputChannelName)
                        ->withEndpointId($endpointName)
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptor::create(
                        $calculatorWithOne,
                        ServiceActivatorBuilder::create($calculatorWithOne, "sum"),
                        MethodInterceptor::DEFAULT_PRECEDENCE,
                        ""
                    )
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptor::create(
                        $calculatorWithTwo,
                        ServiceActivatorBuilder::create($calculatorWithTwo, "sum"),
                        MethodInterceptor::DEFAULT_PRECEDENCE,
                        ""
                    )
                )
                ->registerAroundMethodInterceptor(
                    AroundInterceptorReference::create(
                        $calculatorWithOne, "sum",
                        1, ""
                    )
                )
                ->registerAroundMethodInterceptor(
                    AroundInterceptorReference::create(
                        $calculatorWithTwoAround, "sum",
                        1, ""
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptor::create(
                        $calculatorWithOne,
                        ServiceActivatorBuilder::create($calculatorWithOne, "multiply"),
                        MethodInterceptor::DEFAULT_PRECEDENCE,
                        ""
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptor::create(
                        $calculatorWithTwo,
                        ServiceActivatorBuilder::create($calculatorWithTwo, "multiply"),
                        MethodInterceptor::DEFAULT_PRECEDENCE,
                        ""
                    )
                )
                ->registerConsumerFactory(new EventDrivenConsumerBuilder())
                ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
                        $calculatorWithTwo => CalculatingService::create(2),
                        $calculatorWithTwoAround => CalculatingServiceInterceptorExample::create(2)
                ]));

        $messageChannel = $messagingSystemConfiguration->getMessageChannelByName($inputChannelName);
        $outputChannel = QueueChannel::create();

        $messageChannel->send(
            MessageBuilder::withPayload(0)
                ->setReplyChannel($outputChannel)
                ->build()
        );

        $this->assertEquals(
            10,
            $outputChannel->receive()->getPayload()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_registering_interceptor_with_input_channel()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty())
            ->registerAfterMethodInterceptor(
                MethodInterceptor::create(
                    "some",
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(3), "multiply")
                        ->withInputChannelName("some"),
                    \SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor::DEFAULT_PRECEDENCE,
                    CalculatingService::class
                )
            );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_registering_interceptor_with_output_channel()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty())
            ->registerAfterMethodInterceptor(
                \SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor::create(
                    "some",
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(3), "multiply")
                        ->withOutputMessageChannel("some"),
                    MethodInterceptor::DEFAULT_PRECEDENCE,
                    CalculatingService::class
                )
            );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_registering_interceptors_with_precedence()
    {
        $inputChannelName = "inputChannel";
        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty())
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), "sum")
                        ->withInputChannelName($inputChannelName)
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptor::create(
                        "some",
                        ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(3), "multiply"),
                        3,
                        CalculatingService::class
                    )
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptor::create(
                        "some",
                        ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "sum"),
                        1,
                        CalculatingService::class
                    )
                )
                ->registerAfterMethodInterceptor(
                    \SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor::create(
                        "some",
                        ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "multiply"),
                        3,
                        CalculatingService::class
                    )
                )
                ->registerAfterMethodInterceptor(
                    \SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor::create(
                        "some",
                        ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), "sum"),
                        1,
                        CalculatingService::class
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_generating_random_id_if_no_endpoint_id_passed()
    {
        MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty())
            ->registerMessageHandler(DumbMessageHandlerBuilder::createSimple())
            ->registerMessageHandler(DumbMessageHandlerBuilder::createSimple());

        $this->assertTrue(true);
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_trying_to_register_two_channels_with_same_names()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty())
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel("some"))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel("some"));
    }
}