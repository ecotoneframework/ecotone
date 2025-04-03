<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Channel\MessageChannelInterceptorAdapter;
use Ecotone\Messaging\Channel\PublishSubscribeChannel;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Channel\SimpleChannelInterceptorBuilder;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ConsoleCommandConfiguration;
use Ecotone\Messaging\Config\ConsoleCommandParameter;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\GatewayProxyReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceCacheConfiguration;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\EventDriven\EventDrivenConsumerBuilder;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use Ecotone\Messaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingConsumer\PollOrThrow\PollOrThrowMessageHandlerConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Gateway\MessagingEntrypointWithHeadersPropagation;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptorBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\PollableChannel;
use Ecotone\Messaging\Precedence;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Exception;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\CalculatingServiceInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Gateway\CombinedGatewayExample;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Gateway\SingleMethodGatewayExample;
use Test\Ecotone\Messaging\Fixture\Annotation\ModuleConfiguration\ExampleModuleConfiguration;
use Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling\DeadLetter\OrderService;
use Test\Ecotone\Messaging\Fixture\Endpoint\ConsumerContinuouslyWorkingService;
use Test\Ecotone\Messaging\Fixture\Handler\DumbMessageHandlerBuilder;
use Test\Ecotone\Messaging\Fixture\Handler\ExceptionMessageHandler;
use Test\Ecotone\Messaging\Fixture\Handler\Gateway\MultipleMethodsGatewayExample;
use Test\Ecotone\Messaging\Fixture\Handler\NoReturnMessageHandler;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallWithAnnotationFromMethodInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\StubCallSavingService;
use Test\Ecotone\Messaging\Fixture\InterceptedBridge\BridgeExampleIncomplete;
use Test\Ecotone\Messaging\Fixture\SameChannelAndRouting\SomeTestCommandHandler;
use Test\Ecotone\Messaging\Fixture\SameChannelAndRouting\SomeTestEventHandler;
use Test\Ecotone\Messaging\Fixture\Service\CalculatingService;
use Test\Ecotone\Messaging\Fixture\Service\CalculatingServiceForAsynchronousScenario;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceCalculatingService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceWithoutReturnValue;
use Test\Ecotone\Messaging\Fixture\Service\ServiceWithReturnValue;
use Test\Ecotone\Messaging\Unit\Channel\TestChannelInterceptor;
use Test\Ecotone\Messaging\Unit\MessagingTestCase;

;

/**
 * Class ApplicationTest
 * @package Ecotone\Messaging\Config
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class MessagingSystemConfigurationTest extends MessagingTestCase
{
    /**
     * @throws ConfigurationException
     * @throws Exception
     * @throws MessagingException
     */
    public function test_run_event_driven_consumer()
    {
        $subscribableChannelName = 'input';
        $subscribableChannel = DirectChannel::create();
        $messageHandler = NoReturnMessageHandler::create();

        $messagingSystem = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler(DumbMessageHandlerBuilder::create($messageHandler, $subscribableChannelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($subscribableChannelName, $subscribableChannel))
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $messagingSystem->getMessageChannelByName($subscribableChannelName)->send(MessageBuilder::withPayload('a')->build());

        $this->assertTrue($messageHandler->wasCalled());
    }

    /**
     * @return MessagingSystemConfiguration
     * @throws MessagingException
     */
    private function createMessagingSystemConfiguration(): MessagingSystemConfiguration
    {
        return MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
    }

    /**
     * @throws MessagingException
     */
    public function test_registering_module_with_extension_objects()
    {
        $exampleModuleConfiguration = ExampleModuleConfiguration::createEmpty();
        MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createWith([$exampleModuleConfiguration], [new stdClass(), ServiceWithoutReturnValue::create()]));

        $this->assertEquals(
            ExampleModuleConfiguration::createWithExtensions([new stdClass()]),
            $exampleModuleConfiguration
        );
    }

    /**
     * @throws MessagingException
     */
    public function test_registering_extension_object_from_service_configuration()
    {
        $exampleModuleConfiguration = ExampleModuleConfiguration::createEmpty();
        MessagingSystemConfiguration::prepareWithDefaults(
            InMemoryModuleMessaging::createWith(
                [$exampleModuleConfiguration],
                []
            ),
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([new stdClass()])
        );

        $this->assertEquals(
            ExampleModuleConfiguration::createWithExtensions([new stdClass()]),
            $exampleModuleConfiguration
        );
    }

    /**
     * @throws MessagingException
     */
    public function test_skipping_module_registration()
    {
        $exampleModuleConfiguration = ExampleModuleConfiguration::createEmpty();
        MessagingSystemConfiguration::prepareWithDefaults(
            InMemoryModuleMessaging::createWith(
                [$exampleModuleConfiguration],
                [new stdClass(), ServiceWithoutReturnValue::create()]
            ),
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(['example'])
        );

        $this->assertEquals(
            ExampleModuleConfiguration::createWithExtensions([]),
            $exampleModuleConfiguration
        );
    }

    public function test_running_pollable_consumer()
    {
        $messageChannelName = 'pollableChannel';
        $messageHandler = NoReturnMessageHandler::create();

        $messagingSystem = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler(DumbMessageHandlerBuilder::create($messageHandler, $messageChannelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($messageChannelName, QueueChannel::create()))
            ->registerConsumerFactory(new PollOrThrowMessageHandlerConsumerBuilder())
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $messagingSystem->getMessageChannelByName($messageChannelName)->send(MessageBuilder::withPayload('a')->build());

        $messagingSystem->run($messagingSystem->list()[0]);

        $this->assertTrue($messageHandler->wasCalled());
    }

    public function test_running_overriding_polling_metadata()
    {
        $messageChannelName = 'pollableChannel';
        $pollableChannel = QueueChannel::create();
        $messageHandler = NoReturnMessageHandler::create();

        $messagingSystem = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler(DumbMessageHandlerBuilder::create($messageHandler, $messageChannelName)->withEndpointId('executor'))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($messageChannelName, $pollableChannel))
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $messagingSystem->getMessageChannelByName($messageChannelName)->send(MessageBuilder::withPayload('a')->build());

        $messagingSystem->run('executor', ExecutionPollingMetadata::createWithDefaults()->withExecutionTimeLimitInMilliseconds(1));

        $this->assertTrue($messageHandler->wasCalled());
    }

    public function test_throwing_exception_if_running_not_existing_consumer()
    {
        $messagingSystem = $this->createMessagingSystemConfiguration()
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $this->expectException(InvalidArgumentException::class);

        $messagingSystem->run('some');
    }

    public function test_throwing_exception_if_registered_asynchronous_for_not_existing_endpoint()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerAsynchronousEndpoint('asyncChannel', 'endpointId')
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());
    }

    public function test_throwing_exception_if_registering_asynchronous_for_not_existing_channel()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'result')
                    ->withEndpointId('endpointId')
                    ->withInputChannelName('inputChannel')
            )
            ->registerAsynchronousEndpoint('asyncChannel', 'endpointId')
            ->registerPollingMetadata(PollingMetadata::create('asyncChannel')->setExecutionAmountLimit(1))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());
    }

    public function test_registering_asynchronous_endpoint()
    {
        $calculatingService = CalculatingService::create(1);
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference($calculatingService, 'result')
                    ->withEndpointId('endpointId')
                    ->withInputChannelName('inputChannel')
            )
            ->registerAsynchronousEndpoint('asyncChannel', 'endpointId')
            ->registerPollingMetadata(PollingMetadata::create('asyncChannel')->setExecutionAmountLimit(1))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('asyncChannel'))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $message = MessageBuilder::withPayload(2)
            ->build();

        /** @var MessageChannel $channel */
        $channel = $configuredMessagingSystem->getMessageChannelByName('inputChannel');

        $channel->send($message);
        $this->assertNull($calculatingService->getLastResult());
        $configuredMessagingSystem->run('asyncChannel');
        $this->assertEquals(2, $calculatingService->getLastResult());
    }

    public function test_registering_asynchronous_endpoint_with_two_channels()
    {
        $calculatingService = CalculatingService::create(1);
        $entrypointChannelName = 'inputChannel';
        $channelNameOne = 'asyncChannel1';
        $channelNameTwo = 'asyncChannel2';
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference($calculatingService, 'result')
                    ->withEndpointId('endpointId')
                    ->withInputChannelName($entrypointChannelName)
            )
            ->registerAsynchronousEndpoint([$channelNameOne, $channelNameTwo], 'endpointId')
            ->registerPollingMetadata(PollingMetadata::create($channelNameOne)->withTestingSetup())
            ->registerPollingMetadata(PollingMetadata::create($channelNameTwo)->withTestingSetup())
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($channelNameOne))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($channelNameTwo))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        /** @var MessageChannel $channel */
        $channel = $configuredMessagingSystem->getMessageChannelByName($entrypointChannelName);

        $channel->send(MessageBuilder::withPayload(2)->build());
        $this->assertNull($calculatingService->getLastResult());
        $configuredMessagingSystem->run($channelNameOne);
        $this->assertNull($calculatingService->getLastResult());
        $configuredMessagingSystem->run($channelNameTwo);
        $this->assertEquals(2, $calculatingService->getLastResult());
    }

    public function test_registering_asynchronous_endpoint_with_three_channels()
    {
        $calculatingService = CalculatingService::create(1);
        $entrypointChannelName = 'inputChannel';
        $channelNameOne = 'asyncChannel1';
        $channelNameTwo = 'asyncChannel2';
        $channelNameThree = 'asyncChannel3';
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference($calculatingService, 'result')
                    ->withEndpointId('endpointId')
                    ->withInputChannelName($entrypointChannelName)
            )
            ->registerAsynchronousEndpoint([$channelNameOne, $channelNameTwo, $channelNameThree], 'endpointId')
            ->registerPollingMetadata(PollingMetadata::create($channelNameOne)->withTestingSetup())
            ->registerPollingMetadata(PollingMetadata::create($channelNameTwo)->withTestingSetup())
            ->registerPollingMetadata(PollingMetadata::create($channelNameThree)->withTestingSetup())
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($channelNameOne))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($channelNameTwo))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($channelNameThree))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        /** @var MessageChannel $channel */
        $channel = $configuredMessagingSystem->getMessageChannelByName($entrypointChannelName);

        $channel->send(MessageBuilder::withPayload(2)->build());
        $this->assertNull($calculatingService->getLastResult());
        $configuredMessagingSystem->run($channelNameOne);
        $this->assertNull($calculatingService->getLastResult());
        $configuredMessagingSystem->run($channelNameTwo);
        $this->assertNull($calculatingService->getLastResult());
        $configuredMessagingSystem->run($channelNameThree);
        $this->assertEquals(2, $calculatingService->getLastResult());
    }

    public function test_registering_before_call_intercepted_asynchronous_endpoint()
    {
        $calculatingService = CalculatingService::create(1);
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference($calculatingService, 'result')
                    ->withEndpointId('endpointId')
                    ->withInputChannelName('inputChannel')
            )
            ->registerAsynchronousEndpoint('asyncChannel', 'endpointId')
            ->registerBeforeMethodInterceptor(MethodInterceptorBuilder::create(
                $calculatingService,
                InterfaceToCall::create(CalculatingService::class, 'sum'),
                1,
                CalculatingService::class
            ))
            ->registerPollingMetadata(PollingMetadata::create('asyncChannel')->setExecutionAmountLimit(1))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('asyncChannel'))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $message = MessageBuilder::withPayload(2)
            ->build();

        /** @var MessageChannel $channel */
        $channel = $configuredMessagingSystem->getMessageChannelByName('inputChannel');

        $channel->send($message);
        $this->assertNull($calculatingService->getLastResult());
        $configuredMessagingSystem->run('asyncChannel');
        $this->assertEquals(3, $calculatingService->getLastResult());
    }

    /**
     * Use this as template for future tests in MessagingSystem, to avoid using low level components wiring
     *
     * @template
     */
    public function test_registering_multiple_asynchronous_message_handlers_with_interceptors()
    {
        $calculatingService = CalculatingServiceForAsynchronousScenario::create(2);
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [CalculatingServiceForAsynchronousScenario::class],
            [$calculatingService],
            ServiceConfiguration::createWithAsynchronicityOnly()
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel('asyncOne'),
                    SimpleMessageChannelBuilder::createQueueChannel('asyncTwo'),
                ])
        );

        $ecotoneLite->sendCommandWithRoutingKey('getResultOne', 2);
        $ecotoneLite->run('asyncOne');
        $this->assertEquals(4, $calculatingService->getLastResult());

        $ecotoneLite->sendCommandWithRoutingKey('getResultTwo', 3);
        $ecotoneLite->run('asyncTwo');
        $this->assertEquals(6, $calculatingService->getLastResult());
    }

    public function test_registering_before_call_intercepted_asynchronous_endpoint_with_two_channels()
    {
        $calculatingService = CalculatingService::create(1);
        $asyncChannelNameOne = 'asyncChannelOne';
        $asyncChannelNameTwo = 'asyncChannelTwo';
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference($calculatingService, 'result')
                    ->withEndpointId('endpointId')
                    ->withInputChannelName('inputChannel')
            )
            ->registerAsynchronousEndpoint([$asyncChannelNameOne, $asyncChannelNameTwo], 'endpointId')
            ->registerBeforeMethodInterceptor(MethodInterceptorBuilder::create(
                $calculatingService,
                InterfaceToCall::create(CalculatingService::class, 'sum'),
                1,
                CalculatingService::class
            ))
            ->registerPollingMetadata(PollingMetadata::create($asyncChannelNameOne)->withTestingSetup())
            ->registerPollingMetadata(PollingMetadata::create($asyncChannelNameTwo)->withTestingSetup())
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($asyncChannelNameOne))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($asyncChannelNameTwo))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        /** @var MessageChannel $channel */
        $channel = $configuredMessagingSystem->getMessageChannelByName('inputChannel');

        $channel->send(MessageBuilder::withPayload(2)->build());
        $this->assertNull($calculatingService->getLastResult());
        $configuredMessagingSystem->run($asyncChannelNameOne);
        $this->assertNull($calculatingService->getLastResult());
        $configuredMessagingSystem->run($asyncChannelNameTwo);
        $this->assertEquals(3, $calculatingService->getLastResult());
    }

    public function test_throwing_exception_if_register_polling_metadata_for_non_existing_endpoint()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerPollingMetadata(PollingMetadata::create('some'))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());
    }

    public function test_registering_presend_interceptor_wish_async_channel()
    {
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), 'result')
                    ->withEndpointId('endpointId')
                    ->withInputChannelName('inputChannel')
            )
            ->registerAsynchronousEndpoint('asyncChannel', 'endpointId')
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('asyncChannel'))
            ->registerBeforeSendInterceptor(
                MethodInterceptorBuilder::create(
                    CalculatingService::create(1),
                    InterfaceToCall::create(CalculatingService::class, 'sum'),
                    1,
                    CalculatingService::class
                )
            )
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $replyChannel = QueueChannel::create();
        $requestMessage = MessageBuilder::withPayload(2)
            ->setReplyChannel($replyChannel)
            ->build();

        /** @var PollableChannel $channel */
        $channel = $configuredMessagingSystem->getMessageChannelByName('inputChannel');

        $channel->send($requestMessage);

        $this->assertEquals(3, $configuredMessagingSystem->getMessageChannelByName('asyncChannel')->receive()->getPayload());
    }

    public function test_registering_presend_interceptor_wish_two_async_channels()
    {
        $asyncChannelOne = 'asyncChannelOne';
        $asyncChannelTwo = 'asyncChannelTwo';
        $calculatingService = CalculatingService::create(0);
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference($calculatingService, 'result')
                    ->withEndpointId('endpointId')
                    ->withInputChannelName('inputChannel')
            )
            ->registerAsynchronousEndpoint([$asyncChannelOne, $asyncChannelTwo], 'endpointId')
            ->registerPollingMetadata(PollingMetadata::create($asyncChannelOne)->withTestingSetup())
            ->registerPollingMetadata(PollingMetadata::create($asyncChannelTwo)->withTestingSetup())
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($asyncChannelOne))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($asyncChannelTwo))
            ->registerBeforeSendInterceptor(
                MethodInterceptorBuilder::create(
                    CalculatingService::create(1),
                    InterfaceToCall::create(CalculatingService::class, 'sum'),
                    1,
                    CalculatingService::class
                )
            )
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        /** @var PollableChannel $channel */
        $channel = $configuredMessagingSystem->getMessageChannelByName('inputChannel');

        $channel->send(MessageBuilder::withPayload(2)->build());
        $this->assertNull($calculatingService->getLastResult());
        $this->assertEquals(3, $configuredMessagingSystem->getMessageChannelByName($asyncChannelOne)->receive()->getPayload());
    }

    public function test_registering_presend_interceptor_polling_channel()
    {
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), 'result')
                    ->withEndpointId('endpointId')
                    ->withInputChannelName('inputChannel')
            )
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('inputChannel'))
            ->registerBeforeSendInterceptor(
                MethodInterceptorBuilder::create(
                    CalculatingService::create(1),
                    InterfaceToCall::create(CalculatingService::class, 'sum'),
                    1,
                    CalculatingService::class
                )
            )
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $replyChannel = QueueChannel::create();
        $requestMessage = MessageBuilder::withPayload(2)
            ->setReplyChannel($replyChannel)
            ->build();

        /** @var PollableChannel $channel */
        $channel = $configuredMessagingSystem->getMessageChannelByName('inputChannel');

        $channel->send($requestMessage);

        $this->assertEquals(3, $configuredMessagingSystem->getMessageChannelByName('inputChannel')->receive()->getPayload());
    }

    public function test_registering_multiple_before_send_interceptors()
    {
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), 'result')
                    ->withEndpointId('endpointId1')
                    ->withInputChannelName('inputChannel1')
            )
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('inputChannel1'))
            ->registerBeforeSendInterceptor(
                MethodInterceptorBuilder::create(
                    CalculatingService::create(1),
                    InterfaceToCall::create(CalculatingService::class, 'sum'),
                    1,
                    CalculatingService::class
                )
            )
            ->registerBeforeSendInterceptor(
                MethodInterceptorBuilder::create(
                    CalculatingService::create(1),
                    InterfaceToCall::create(CalculatingService::class, 'sum'),
                    1,
                    CalculatingService::class
                )
            )
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $replyChannel = QueueChannel::create();
        $requestMessage = MessageBuilder::withPayload(2)
            ->setReplyChannel($replyChannel)
            ->build();

        /** @var PollableChannel $channel */
        $channel = $configuredMessagingSystem->getMessageChannelByName('inputChannel1');

        $channel->send($requestMessage);

        $this->assertEquals(4, $configuredMessagingSystem->getMessageChannelByName('inputChannel1')->receive()->getPayload());
    }

    public function test_not_duplicating_before_send_interceptors()
    {
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), 'result')
                    ->withEndpointId('endpointId1')
                    ->withInputChannelName('inputChannel1')
            )
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), 'result')
                    ->withEndpointId('endpointId2')
                    ->withInputChannelName('inputChannel1')
            )
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('inputChannel1'))
            ->registerBeforeSendInterceptor(
                MethodInterceptorBuilder::create(
                    CalculatingService::create(1),
                    InterfaceToCall::create(CalculatingService::class, 'sum'),
                    1,
                    CalculatingService::class
                )
            )
            ->registerBeforeSendInterceptor(
                MethodInterceptorBuilder::create(
                    CalculatingService::create(1),
                    InterfaceToCall::create(CalculatingService::class, 'sum'),
                    1,
                    CalculatingService::class
                )
            )
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $replyChannel = QueueChannel::create();
        $requestMessage = MessageBuilder::withPayload(2)
            ->setReplyChannel($replyChannel)
            ->build();

        /** @var PollableChannel $channel */
        $channel = $configuredMessagingSystem->getMessageChannelByName('inputChannel1');

        $channel->send($requestMessage);

        $this->assertEquals(4, $configuredMessagingSystem->getMessageChannelByName('inputChannel1')->receive()->getPayload());
    }

    public function test_registering_asynchronous_endpoint_with_in_memory_queue_channel()
    {
        $calculatingService = CalculatingService::create(1);
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference($calculatingService, 'result')
                    ->withEndpointId('endpointId')
                    ->withInputChannelName('inputChannel')
            )
            ->registerAsynchronousEndpoint('fakeAsyncChannel', 'endpointId')
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('fakeAsyncChannel'))
            ->registerPollingMetadata(PollingMetadata::create('fakeAsyncChannel')->withTestingSetup())
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $message = MessageBuilder::withPayload(2)
            ->setReplyChannel(QueueChannel::create())
            ->build();

        /** @var MessageChannel $channel */
        $channel = $configuredMessagingSystem->getMessageChannelByName('inputChannel');

        $channel->send($message);
        $this->assertNull($calculatingService->getLastResult());

        $configuredMessagingSystem->run('fakeAsyncChannel');
        $this->assertEquals(2, $calculatingService->getLastResult());
    }

    public function test_registering_asynchronous_endpoint_with_channel_interceptor()
    {
        $calculatingService = CalculatingService::create(1);
        $channelInterceptor = new TestChannelInterceptor();

        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference($calculatingService, 'result')
                    ->withEndpointId('endpointId')
                    ->withInputChannelName('inputChannel')
            )
            ->registerAsynchronousEndpoint('asyncChannel', 'endpointId')
            ->registerPollingMetadata(PollingMetadata::create('asyncChannel')->setExecutionAmountLimit(1))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('asyncChannel'))
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create('inputChannel', 'ref-name'))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith(['ref-name' => $channelInterceptor]));

        $replyChannel = QueueChannel::create();
        $requestMessage = MessageBuilder::withPayload(2)
            ->setReplyChannel($replyChannel)
            ->build();

        /** @var MessageChannel $channel */
        $channel = $configuredMessagingSystem->getMessageChannelByName('inputChannel');

        $channel->send($requestMessage);

        $this->assertTrue($channelInterceptor->wasPreSendCalled());
        $this->assertSame($requestMessage, $channelInterceptor->getCapturedMessage());
    }

    public function test_registering_with_extension_media_type_serializer_applied_to_application_configuration()
    {
        $this->assertEquals(
            MessagingSystemConfiguration::prepareWithModuleRetrievingService(InMemoryModuleMessaging::createWith([], [ServiceConfiguration::createWithDefaults()]), InterfaceToCallRegistry::createEmpty(), ServiceConfiguration::createWithDefaults()->withDefaultSerializationMediaType(MediaType::APPLICATION_JSON)),
            MessagingSystemConfiguration::prepareWithModuleRetrievingService(InMemoryModuleMessaging::createWith([], [ServiceConfiguration::createWithDefaults()->withDefaultSerializationMediaType(MediaType::APPLICATION_JSON)]), InterfaceToCallRegistry::createEmpty(), ServiceConfiguration::createWithDefaults())
        );
    }

    public function test_throwing_if_passed_two_extension_configurations_for_different_media_type()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepareWithModuleRetrievingService(InMemoryModuleMessaging::createWith([], [ServiceConfiguration::createWithDefaults()->withDefaultSerializationMediaType(MediaType::APPLICATION_JSON), ServiceConfiguration::createWithDefaults()->withDefaultSerializationMediaType(MediaType::APPLICATION_XML)]), InterfaceToCallRegistry::createEmpty(), ServiceConfiguration::createWithDefaults(), ServiceCacheConfiguration::noCache());
    }

    public function test_taking_default_media_type_serializer_from_global_application_configuration_if_passed()
    {
        $this->assertEquals(
            MessagingSystemConfiguration::prepareWithModuleRetrievingService(InMemoryModuleMessaging::createWith([], [ServiceConfiguration::createWithDefaults()]), InterfaceToCallRegistry::createEmpty(), ServiceConfiguration::createWithDefaults()->withDefaultSerializationMediaType(MediaType::APPLICATION_JSON), ServiceCacheConfiguration::noCache()),
            MessagingSystemConfiguration::prepareWithModuleRetrievingService(InMemoryModuleMessaging::createWith([], [ServiceConfiguration::createWithDefaults()->withDefaultSerializationMediaType(MediaType::APPLICATION_XML)]), InterfaceToCallRegistry::createEmpty(), ServiceConfiguration::createWithDefaults()->withDefaultSerializationMediaType(MediaType::APPLICATION_JSON), ServiceCacheConfiguration::noCache())
        );
    }

    public function test_registering_with_default_error_channel_applied_to_application_configuration()
    {
        $this->assertEquals(
            MessagingSystemConfiguration::prepareWithModuleRetrievingService(InMemoryModuleMessaging::createWith([], [ServiceConfiguration::createWithDefaults()]), InterfaceToCallRegistry::createEmpty(), ServiceConfiguration::createWithDefaults()->withDefaultErrorChannel('errorChannel'), ServiceCacheConfiguration::noCache()),
            MessagingSystemConfiguration::prepareWithModuleRetrievingService(InMemoryModuleMessaging::createWith([], [ServiceConfiguration::createWithDefaults()->withDefaultErrorChannel('errorChannel')]), InterfaceToCallRegistry::createEmpty(), ServiceConfiguration::createWithDefaults(), ServiceCacheConfiguration::noCache())
        );
    }

    public function test_throwing_if_passed_two_extension_configurations_for_different_default_error_channel()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepareWithModuleRetrievingService(InMemoryModuleMessaging::createWith([], [ServiceConfiguration::createWithDefaults()->withDefaultErrorChannel('errorChannel1'), ServiceConfiguration::createWithDefaults()->withDefaultErrorChannel('errorChannel2')]), InterfaceToCallRegistry::createEmpty(), ServiceConfiguration::createWithDefaults(), ServiceCacheConfiguration::noCache());
    }

    public function test_taking_default_error_channel_from_global_application_configuration_if_passed()
    {
        $this->assertEquals(
            MessagingSystemConfiguration::prepareWithModuleRetrievingService(InMemoryModuleMessaging::createWith([], [ServiceConfiguration::createWithDefaults()]), InterfaceToCallRegistry::createEmpty(), ServiceConfiguration::createWithDefaults()->withDefaultErrorChannel('errorChannel'), ServiceCacheConfiguration::noCache()),
            MessagingSystemConfiguration::prepareWithModuleRetrievingService(InMemoryModuleMessaging::createWith([], [ServiceConfiguration::createWithDefaults()->withDefaultErrorChannel('inputChannel')]), InterfaceToCallRegistry::createEmpty(), ServiceConfiguration::createWithDefaults()->withDefaultErrorChannel('errorChannel'), ServiceCacheConfiguration::noCache())
        );
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function test_intercepting_channel_before_sending()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());

        $messageChannelName = 'requestChannel';
        $referenceName = 'ref-name';
        $messagingSystemConfiguration
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($messageChannelName))
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create($messageChannelName, $referenceName));

        $channelInterceptor = new TestChannelInterceptor();
        $messagingSystem = $messagingSystemConfiguration->buildMessagingSystemFromConfiguration(
            InMemoryReferenceSearchService::createWith(
                [
                    $referenceName => $channelInterceptor,
                ]
            )
        );

        $message = MessageBuilder::withPayload('testMessage')->build();
        /** @var QueueChannel|MessageChannelInterceptorAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName);

        $preSendModifiedMessage = MessageBuilder::withPayload('preSend')->build();
        $channelInterceptor->setReturnMessageOnPreSend($preSendModifiedMessage);

        $queueChannel->send($message);

        $this->assertEquals(
            $preSendModifiedMessage,
            $queueChannel->receive()
        );
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function test_ordering_channel_interceptors_before_sending()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());

        $messageChannelName = 'requestChannel';
        $referenceNameSecondToCall = 'interceptor-1';
        $referenceNameFirstToCall = 'interceptor-2';
        $messagingSystemConfiguration
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($messageChannelName))
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create($messageChannelName, $referenceNameSecondToCall)->withPrecedence(1))
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create($messageChannelName, $referenceNameFirstToCall)->withPrecedence(2));

        $channelInterceptorSecondToCall = new TestChannelInterceptor();
        $channelInterceptorFirstToCall = new TestChannelInterceptor();
        $messagingSystem = $messagingSystemConfiguration->buildMessagingSystemFromConfiguration(
            InMemoryReferenceSearchService::createWith(
                [
                    $referenceNameSecondToCall => $channelInterceptorSecondToCall,
                    $referenceNameFirstToCall => $channelInterceptorFirstToCall,
                ]
            )
        );

        /** @var QueueChannel|MessageChannelInterceptorAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName);

        $message = MessageBuilder::withPayload('testMessage')->build();
        $messageFirstModification = MessageBuilder::withPayload('preSend1')->build();
        $messageSecondModification = MessageBuilder::withPayload('preSend2')->build();

        $channelInterceptorFirstToCall->setReturnMessageOnPreSend($messageFirstModification);
        $channelInterceptorSecondToCall->setReturnMessageOnPreSend($messageSecondModification);

        $queueChannel->send($message);

        $this->assertEquals(
            $messageSecondModification,
            $queueChannel->receive()
        );
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function test_intercepting_by_stopping_message_flow()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());

        $messageChannelName = 'requestChannel';
        $referenceName = 'ref-name';
        $messagingSystemConfiguration
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($messageChannelName))
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create($messageChannelName, $referenceName));

        $channelInterceptor = new TestChannelInterceptor();
        $channelInterceptor->setReturnNullOnPreSend(true);
        $messagingSystem = $messagingSystemConfiguration->buildMessagingSystemFromConfiguration(
            InMemoryReferenceSearchService::createWith(
                [
                    $referenceName => $channelInterceptor,
                ]
            )
        );

        $message = MessageBuilder::withPayload('testMessage')->build();
        /** @var QueueChannel|MessageChannelInterceptorAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName);

        $channelInterceptor->setReturnNullOnPreSend(true);

        $queueChannel->send($message);

        $this->assertEquals(
            null,
            $queueChannel->receive()
        );
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function test_intercepting_after_sending_to_inform_it_was_successful()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());

        $messageChannelName = 'requestChannel';
        $referenceName = 'ref-name';
        $messagingSystemConfiguration
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($messageChannelName))
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create($messageChannelName, $referenceName));

        $channelInterceptor = new TestChannelInterceptor();
        $messagingSystem = $messagingSystemConfiguration->buildMessagingSystemFromConfiguration(
            InMemoryReferenceSearchService::createWith(
                [
                    $referenceName => $channelInterceptor,
                ]
            )
        );

        $message = MessageBuilder::withPayload('testMessage')->build();
        /** @var QueueChannel|MessageChannelInterceptorAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName);

        $queueChannel->send($message);

        $this->assertTrue($channelInterceptor->wasPreSendCalled());
        $this->assertTrue($channelInterceptor->wasPostSendCalled());
        $this->assertSame($message, $channelInterceptor->getCapturedMessage());
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function test_intercepting_after_sending_to_inform_about_failure_handling_after_exception_occurred()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());

        $messageChannelName = 'requestChannel';
        $referenceName = 'ref-name';
        $messagingSystemConfiguration
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel($messageChannelName))
            ->registerMessageHandler(DumbMessageHandlerBuilder::create(ExceptionMessageHandler::create(), $messageChannelName))
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create($messageChannelName, $referenceName));

        $channelInterceptor = new TestChannelInterceptor(null, true, true);
        $messagingSystem = $messagingSystemConfiguration->buildMessagingSystemFromConfiguration(
            InMemoryReferenceSearchService::createWith(
                [
                    $referenceName => $channelInterceptor,
                ]
            )
        );

        $message = MessageBuilder::withPayload('testMessage')->build();
        /** @var QueueChannel|MessageChannelInterceptorAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName);

        $queueChannel->send($message);

        $this->assertTrue($channelInterceptor->wasPreSendCalled());
        $this->assertTrue($channelInterceptor->wasAfterSendCompletionCalled());
        $this->assertSame($message, $channelInterceptor->getCapturedMessage());
        $this->assertInstanceOf(\InvalidArgumentException::class, $channelInterceptor->getCapturedException());
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function test_intercepting_with_multiple_channels()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());

        $messageChannelName1 = 'requestChannel1';
        $messageChannelName2 = 'requestChannel2';
        $referenceName1 = 'ref-name1';
        $referenceName2 = 'ref-name2';
        $messagingSystemConfiguration
            ->registerMessageChannel(SimpleMessageChannelBuilder::createPublishSubscribeChannel($messageChannelName1))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($messageChannelName2))
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create($messageChannelName1, $referenceName1))
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create($messageChannelName2, $referenceName2));

        $channelInterceptor1 = new TestChannelInterceptor();
        $channelInterceptor2 = new TestChannelInterceptor();
        $messagingSystem = $messagingSystemConfiguration->buildMessagingSystemFromConfiguration(
            InMemoryReferenceSearchService::createWith(
                [
                    $referenceName1 => $channelInterceptor1,
                    $referenceName2 => $channelInterceptor2,
                ]
            )
        );

        $message = MessageBuilder::withPayload('testMessage')->build();
        /** @var QueueChannel|MessageChannelInterceptorAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName2);

        $preSendModifiedMessage = MessageBuilder::withPayload('preSend')->build();
        $channelInterceptor2->setReturnMessageOnPreSend($preSendModifiedMessage);

        $queueChannel->send($message);

        $this->assertEquals(
            $preSendModifiedMessage,
            $queueChannel->receive()
        );
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function test_registering_channel_interceptor_with_regex()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());

        $messageChannelName = 'requestChannel';
        $referenceName = 'ref-name';
        $messagingSystemConfiguration
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($messageChannelName))
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create('request*', $referenceName));

        $channelInterceptor = new TestChannelInterceptor();
        $messagingSystem = $messagingSystemConfiguration->buildMessagingSystemFromConfiguration(
            InMemoryReferenceSearchService::createWith(
                [
                    $referenceName => $channelInterceptor,
                ]
            )
        );

        $message = MessageBuilder::withPayload('testMessage')->build();
        /** @var QueueChannel|MessageChannelInterceptorAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName);

        $preSendModifiedMessage = MessageBuilder::withPayload('preSend')->build();
        $channelInterceptor->setReturnMessageOnPreSend($preSendModifiedMessage);

        $queueChannel->send($message);

        $this->assertEquals(
            $preSendModifiedMessage,
            $queueChannel->receive()
        );
    }

    /**
     * @throws MessagingException
     */
    public function test_creating_implicit_direct_channel_if_not_exists()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());

        $inputMessageChannelName = 'inputChannelName';
        $messageHandler = NoReturnMessageHandler::create();
        $messagingSystem = $messagingSystemConfiguration
            ->registerMessageHandler(DumbMessageHandlerBuilder::create($messageHandler, $inputMessageChannelName))
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->buildMessagingSystemFromConfiguration(
                InMemoryReferenceSearchService::createEmpty()
            );

        $messagingSystem->getMessageChannelByName($inputMessageChannelName)
            ->send(MessageBuilder::withPayload('some')->build());

        $this->assertTrue($messageHandler->wasCalled());
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function test_creating_default_channel_configuration_if_not_exists()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());

        $inputMessageChannelName = 'inputChannelName';
        $messageHandler = NoReturnMessageHandler::create();
        $messagingSystem = $messagingSystemConfiguration
            ->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($inputMessageChannelName))
            ->registerMessageHandler(DumbMessageHandlerBuilder::create($messageHandler, $inputMessageChannelName))
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $messagingSystem->getMessageChannelByName($inputMessageChannelName)
            ->send(MessageBuilder::withPayload('some')->build());

        $this->assertInstanceOf(PublishSubscribeChannel::class, $messagingSystem->getMessageChannelByName($inputMessageChannelName));
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function test_replacing_implicit_direct_channel_with_real_channel_if_passed()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());

        $inputMessageChannelName = 'inputChannelName';
        $messageHandler = NoReturnMessageHandler::create();
        $messagingSystem = $messagingSystemConfiguration
            ->registerMessageHandler(DumbMessageHandlerBuilder::create($messageHandler, $inputMessageChannelName))
            ->registerConsumerFactory(new PollOrThrowMessageHandlerConsumerBuilder())
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($inputMessageChannelName))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $messagingSystem->getMessageChannelByName($inputMessageChannelName)
            ->send(MessageBuilder::withPayload('some')->build());

        $this->assertFalse($messageHandler->wasCalled(), 'Queue channel was registered, so without explicit calling the consumer it should not be ever called');
    }

    public function test_registering_endpoint_with_error_channel()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());

        $inputMessageChannelName = 'inputChannelName';
        $messageHandler = ExceptionMessageHandler::create();
        $endpointName = 'pollableName';
        $errorChannel = QueueChannel::create();
        $messagingSystem = $messagingSystemConfiguration
            ->registerMessageHandler(
                DumbMessageHandlerBuilder::create($messageHandler, $inputMessageChannelName)
                    ->withEndpointId($endpointName)
            )
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($inputMessageChannelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create('error', $errorChannel))
            ->registerPollingMetadata(
                PollingMetadata::create($endpointName)
                    ->setHandledMessageLimit(1)
                    ->setErrorChannelName('error')
            )
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $messagingSystem->getMessageChannelByName($inputMessageChannelName)
            ->send(MessageBuilder::withPayload('some')->build());

        $messagingSystem->run($endpointName);

        $this->assertNotNull($errorChannel->receive());
    }

    public function test_registering_endpoint_with_default_error_channel()
    {
        $applicationConfiguration = ServiceConfiguration::createWithDefaults()
                                        ->withDefaultErrorChannel('error');
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithModuleRetrievingService(InMemoryModuleMessaging::createEmpty(), InterfaceToCallRegistry::createEmpty(), $applicationConfiguration, ServiceCacheConfiguration::noCache());

        $inputMessageChannelName = 'inputChannelName';
        $messageHandler = ExceptionMessageHandler::create();
        $endpointName = 'pollableName';
        $errorChannel = QueueChannel::create();
        $messagingSystem = $messagingSystemConfiguration
            ->registerMessageHandler(
                DumbMessageHandlerBuilder::create($messageHandler, $inputMessageChannelName)
                    ->withEndpointId($endpointName)
            )
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($inputMessageChannelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create('error', $errorChannel))
            ->registerPollingMetadata(
                PollingMetadata::create($endpointName)
                    ->setHandledMessageLimit(1)
            )
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $messagingSystem->getMessageChannelByName($inputMessageChannelName)
            ->send(MessageBuilder::withPayload('some')->build());

        $messagingSystem->run($endpointName);

        $this->assertNotNull($errorChannel->receive());
    }

    public function test_disabling_error_channel_for_endpoint()
    {
        $applicationConfiguration = ServiceConfiguration::createWithDefaults()
            ->withDefaultErrorChannel('error');
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithModuleRetrievingService(
            InMemoryModuleMessaging::createEmpty(),
            InterfaceToCallRegistry::createEmpty(),
            $applicationConfiguration,
            ServiceCacheConfiguration::noCache()
        );

        $inputMessageChannelName = 'inputChannelName';
        $messageHandler = ExceptionMessageHandler::create();
        $endpointName = 'pollableName';
        $errorChannel = QueueChannel::create();
        $messagingSystem = $messagingSystemConfiguration
            ->registerMessageHandler(
                DumbMessageHandlerBuilder::create($messageHandler, $inputMessageChannelName)
                    ->withEndpointId($endpointName)
            )
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($inputMessageChannelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create('error', $errorChannel))
            ->registerPollingMetadata(
                PollingMetadata::create($endpointName)
                    ->setExecutionTimeLimitInMilliseconds(1)
                    ->setHandledMessageLimit(1)
                    ->setEnabledErrorChannel(false)
            )
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $this->expectException(\InvalidArgumentException::class);

        $messagingSystem->getMessageChannelByName($inputMessageChannelName)
            ->send(MessageBuilder::withPayload('some')->build());

        $messagingSystem->run($endpointName);
    }

    public function test_throwing_exception_if_registering_inbound_channel_adapter_with_same_names()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
        $requestChannelName = 'requestChannelName';
        $endpointName = 'pollableName';

        $this->expectException(ConfigurationException::class);

        $messagingSystemConfiguration
            ->registerConsumer(
                InboundChannelAdapterBuilder::createWithDirectObject(
                    $requestChannelName,
                    ConsumerContinuouslyWorkingService::createWithReturn(5),
                    InterfaceToCall::create(ConsumerContinuouslyWorkingService::class, 'executeReturn')
                )->withEndpointId($endpointName)
            )
            ->registerConsumer(
                InboundChannelAdapterBuilder::createWithDirectObject(
                    $requestChannelName,
                    ConsumerContinuouslyWorkingService::createWithReturn(5),
                    InterfaceToCall::create(ConsumerContinuouslyWorkingService::class, 'executeReturn')
                )->withEndpointId($endpointName)
            );
    }

    /**
     * @throws ConfigurationException
     * @throws Exception
     * @throws MessagingException
     */
    public function test_intercepting_channel_adapter()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
        $requestChannelName = 'requestChannelName';
        $endpointName = 'pollableName';

        $lastServiceFromChain = CalculatingService::create(0);
        $messagingSystem = $messagingSystemConfiguration
            ->registerConsumer(
                InboundChannelAdapterBuilder::createWithDirectObject(
                    $requestChannelName,
                    ConsumerContinuouslyWorkingService::createWithReturn(5),
                    InterfaceToCall::create(ConsumerContinuouslyWorkingService::class, 'executeReturn')
                )->withEndpointId($endpointName)
            )
            ->registerMessageHandler(ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(2), 'sum')->withInputChannelName($requestChannelName))
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerPollingMetadata(
                PollingMetadata::create($endpointName)
                    ->withTestingSetup()
            )
            ->registerBeforeMethodInterceptor(
                MethodInterceptorBuilder::create(
                    CalculatingService::create(2),
                    InterfaceToCall::create(CalculatingService::class, 'multiply'),
                    1,
                    ConsumerContinuouslyWorkingService::class
                )
            )
            /** will not contribute to calculations, as will add after final service was called */
            ->registerAroundMethodInterceptor(
                AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(
                    InterfaceToCallRegistry::createEmpty(),
                    CalculatingServiceInterceptorExample::create(1),
                    'sumAfterCalling',
                    1,
                    ConsumerContinuouslyWorkingService::class
                )
            )
            ->registerAfterMethodInterceptor(
                MethodInterceptorBuilder::create(
                    CalculatingService::create(2),
                    InterfaceToCall::create(CalculatingService::class, 'multiply'),
                    1,
                    ConsumerContinuouslyWorkingService::class
                )
            )
            ->registerAfterMethodInterceptor(
                MethodInterceptorBuilder::create(
                    $lastServiceFromChain,
                    InterfaceToCall::create(CalculatingService::class, 'result'),
                    1,
                    ConsumerContinuouslyWorkingService::class
                )
            )
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $messagingSystem->run($endpointName);

        $this->assertEquals(26, $lastServiceFromChain->getLastResult());
    }

    public function test_intercepting_channel_adapter_with_void_services_by_passing_through_message()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
        $requestChannelName = 'requestChannelName';
        $endpointName = 'pollableName';
        $interceptingHandler = NoReturnMessageHandler::create();

        $messagingSystem = $messagingSystemConfiguration
            ->registerConsumer(
                InboundChannelAdapterBuilder::createWithDirectObject(
                    $requestChannelName,
                    ConsumerContinuouslyWorkingService::createWithReturn(5),
                    InterfaceToCall::create(ConsumerContinuouslyWorkingService::class, 'executeReturn')
                )->withEndpointId($endpointName)
            )
            ->registerMessageHandler(ServiceActivatorBuilder::createWithDirectReference(ServiceWithReturnValue::create(), 'getName')->withInputChannelName($requestChannelName))
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerPollingMetadata(
                PollingMetadata::create($endpointName)
                    ->setHandledMessageLimit(1)
            )
            ->registerBeforeMethodInterceptor(
                MethodInterceptorBuilder::create(
                    $interceptingHandler,
                    InterfaceToCall::create(NoReturnMessageHandler::class, 'handle'),
                    1,
                    ConsumerContinuouslyWorkingService::class
                )
            )
            ->registerAroundMethodInterceptor(
                AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(
                    InterfaceToCallRegistry::createEmpty(),
                    $interceptingHandler,
                    'handle',
                    1,
                    ConsumerContinuouslyWorkingService::class
                )
            )
            ->registerAfterMethodInterceptor(
                MethodInterceptorBuilder::create(
                    $interceptingHandler,
                    InterfaceToCall::create(NoReturnMessageHandler::class, 'handle'),
                    1,
                    ConsumerContinuouslyWorkingService::class
                )
            )
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $messagingSystem->run($endpointName);

        $this->assertEquals(3, $interceptingHandler->getCallCount());
    }

    /**
     * @throws MessagingException
     */
    public function test_registering_interceptors_using_pointcuts_for_message_handler()
    {
        $endpointName = 'endpointName';
        $inputChannelName = 'inputChannel';
        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum')
                        ->withInputChannelName($inputChannelName)
                        ->withEndpointId($endpointName)
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        CalculatingService::create(2),
                        InterfaceToCall::create(CalculatingService::class, 'sum'),
                        Precedence::DEFAULT_PRECEDENCE,
                        CalculatingService::class
                    )
                )
                ->registerAroundMethodInterceptor(
                    AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(
                        InterfaceToCallRegistry::createEmpty(),
                        CalculatingServiceInterceptorExample::create(2),
                        'sum',
                        1,
                        CalculatingService::class
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        CalculatingService::create(3),
                        InterfaceToCall::create(CalculatingService::class, 'multiply'),
                        Precedence::DEFAULT_PRECEDENCE,
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
     * @throws MessagingException
     */
    public function test_intercepting_using_interceptor_converter_to_retrieve_annotations_from_intercepted_handler()
    {
        $endpointName = 'endpointName';
        $inputChannelName = 'inputChannel';
        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(StubCallSavingService::createWithReturnType('some'), 'methodWithAnnotationWithReturnType')
                        ->withInputChannelName($inputChannelName)
                        ->withEndpointId($endpointName)
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        CallWithAnnotationFromMethodInterceptorExample::create(),
                        InterfaceToCall::create(CallWithAnnotationFromMethodInterceptorExample::class, 'callWithMethodAnnotation'),
                        Precedence::DEFAULT_PRECEDENCE,
                        StubCallSavingService::class
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        CallWithAnnotationFromMethodInterceptorExample::create(),
                        InterfaceToCall::create(CallWithAnnotationFromMethodInterceptorExample::class, 'callWithMethodAnnotation'),
                        Precedence::DEFAULT_PRECEDENCE,
                        StubCallSavingService::class
                    )
                )
                ->registerConsumerFactory(new EventDrivenConsumerBuilder())
                ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $messageChannel = $messagingSystemConfiguration->getMessageChannelByName($inputChannelName);
        $outputChannel = QueueChannel::create();

        $message = MessageBuilder::withPayload(0)
            ->setReplyChannel($outputChannel)
            ->build();
        $messageChannel->send($message);

        $this->assertEquals(0, $outputChannel->receive()->getPayload());
    }

    /**
     * @throws MessagingException
     */
    public function test_registering_interceptors_by_reference_names()
    {
        $endpointName = 'endpointName';
        $inputChannelName = 'inputChannel';
        $calculatorWithOne = 'calculatorWithOne';
        $calculatorWithTwo = 'calculatorWithTwo';
        $calculatorWithTwoAround = 'calculatorWithTwoAround';
        $objects = [
            $calculatorWithOne => CalculatingService::create(1),
            $calculatorWithTwo => CalculatingService::create(2),
            $calculatorWithTwoAround => CalculatingServiceInterceptorExample::create(2),
        ];

        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepareWithModuleRetrievingService(
                InMemoryModuleMessaging::createEmpty(),
                InterfaceToCallRegistry::createEmpty(),
                ServiceConfiguration::createWithDefaults(),
                ServiceCacheConfiguration::noCache()
            )
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum')
                        ->withRequiredInterceptorNames([$calculatorWithTwo, CalculatingServiceInterceptorExample::class])
                        ->withInputChannelName($inputChannelName)
                        ->withEndpointId($endpointName)
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        Reference::to($calculatorWithOne),
                        InterfaceToCall::create(CalculatingService::class, 'sum'),
                        Precedence::DEFAULT_PRECEDENCE,
                        ''
                    )
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        Reference::to($calculatorWithTwo),
                        InterfaceToCall::create(CalculatingService::class, 'sum'),
                        Precedence::DEFAULT_PRECEDENCE,
                        ''
                    )
                )
                ->registerAroundMethodInterceptor(
                    AroundInterceptorBuilder::create(
                        $calculatorWithOne,
                        InterfaceToCall::create(CalculatingService::class, 'sum'),
                        1,
                        '',
                        []
                    )
                )
                ->registerAroundMethodInterceptor(
                    AroundInterceptorBuilder::create(
                        $calculatorWithTwoAround,
                        InterfaceToCall::create(CalculatingServiceInterceptorExample::class, 'sum'),
                        1,
                        '',
                        []
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        Reference::to($calculatorWithOne),
                        InterfaceToCall::create(CalculatingService::class, 'multiply'),
                        Precedence::DEFAULT_PRECEDENCE,
                        ''
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        Reference::to($calculatorWithTwo),
                        InterfaceToCall::create(CalculatingService::class, 'multiply'),
                        Precedence::DEFAULT_PRECEDENCE,
                        ''
                    )
                )
                ->registerConsumerFactory(new EventDrivenConsumerBuilder())
                ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith($objects));

        $messageChannel = $messagingSystemConfiguration->getMessageChannelByName($inputChannelName);
        $outputChannel = QueueChannel::create();

        $messageChannel->send(
            MessageBuilder::withPayload(0)
                ->setReplyChannel($outputChannel)
                ->build()
        );

        // [0 + 2 (before) + 2 (around) + 1 (endpoint)] * 2 (after) = 10
        $this->assertEquals(
            10,
            $outputChannel->receive()->getPayload()
        );
    }

    public function test_combing_gateway_for_same_reference()
    {
        $buyGateway = GatewayProxyBuilder::create('combinedGateway', CombinedGatewayExample::class, 'buy', 'buy');
        $sellGateway = GatewayProxyBuilder::create('combinedGateway', CombinedGatewayExample::class, 'sell', 'sell');

        $messagingSystem = $this->createMessagingSystemConfiguration()
            ->registerGatewayBuilder($buyGateway)
            ->registerGatewayBuilder($sellGateway)
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('buy'))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('sell'))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        /** @var CombinedGatewayExample $combinedGateway */
        $combinedGateway = $messagingSystem
            ->getGatewayByName('combinedGateway');

        $combinedGateway->buy();
        $this->assertNotNull($messagingSystem->getMessageChannelByName('buy')->receive());
        $this->assertNull($messagingSystem->getMessageChannelByName('sell')->receive());

        $combinedGateway->sell();
        $this->assertNull($messagingSystem->getMessageChannelByName('buy')->receive());
        $this->assertNotNull($messagingSystem->getMessageChannelByName('sell')->receive());
    }

    public function test_building_non_proxy_gateway_from_multiple_methods()
    {
        $buyGatewayBuilder = GatewayProxyBuilder::create('combinedGateway', CombinedGatewayExample::class, 'buy', 'buy');
        $sellGatewayBuilder = GatewayProxyBuilder::create('combinedGateway', CombinedGatewayExample::class, 'sell', 'sell');

        $messagingSystem = $this->createMessagingSystemConfiguration()
            ->registerGatewayBuilder($buyGatewayBuilder)
            ->registerGatewayBuilder($sellGatewayBuilder)
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('buy'))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('sell'))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $buyGateway = $messagingSystem
            ->getNonProxyGatewayByName($buyGatewayBuilder->getProxyMethodReference());
        $sellGateway = $messagingSystem
            ->getNonProxyGatewayByName($sellGatewayBuilder->getProxyMethodReference());

        $buyGateway->execute([]);
        $this->assertNotNull($messagingSystem->getMessageChannelByName('buy')->receive());
        $this->assertNull($messagingSystem->getMessageChannelByName('sell')->receive());

        $sellGateway->execute([]);
        $this->assertNull($messagingSystem->getMessageChannelByName('buy')->receive());
        $this->assertNotNull($messagingSystem->getMessageChannelByName('sell')->receive());
    }

    public function test_registering_multiple_gateways()
    {
        $buyGateway = GatewayProxyBuilder::create('combinedGateway', CombinedGatewayExample::class, 'buy', 'buy');
        $sellGateway = GatewayProxyBuilder::create('combinedGateway', CombinedGatewayExample::class, 'sell', 'sell');
        $buyGateway2 = GatewayProxyBuilder::create('gateway', SingleMethodGatewayExample::class, 'buy', 'buy');

        $messagingSystem = $this->createMessagingSystemConfiguration()
            ->registerGatewayBuilder($buyGateway)
            ->registerGatewayBuilder($sellGateway)
            ->registerGatewayBuilder($buyGateway2)
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('buy'))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('sell'))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $this->assertNotNull($messagingSystem->getNonProxyGatewayByName($buyGateway->getProxyMethodReference()));
        $this->assertNotNull($messagingSystem->getNonProxyGatewayByName($sellGateway->getProxyMethodReference()));
        $this->assertNotNull($messagingSystem->getNonProxyGatewayByName($buyGateway2->getProxyMethodReference()));
    }

    public function test_building_non_proxy_gateway_for_single_method()
    {
        $buyGateway = GatewayProxyBuilder::create('combinedGateway', SingleMethodGatewayExample::class, 'buy', 'buy')
            ->withReplyChannel('replyChannel');

        $messagingSystem = $this->createMessagingSystemConfiguration()
            ->registerGatewayBuilder($buyGateway)
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('buy'))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('sell'))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('replyChannel'))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $messagingSystem->getMessageChannelByName('replyChannel')->send(MessageBuilder::withPayload('some')->build());

        $combinedGateway = $messagingSystem
            ->getNonProxyGatewayByName($buyGateway->getProxyMethodReference());

        $this->assertEquals('some', $combinedGateway->execute([]));
        $this->assertNotNull($messagingSystem->getMessageChannelByName('buy')->receive());
        $this->assertEquals(
            [new GatewayProxyReference('combinedGateway', SingleMethodGatewayExample::class)],
            $messagingSystem->getGatewayList()
        );
    }

    public function test_throwing_exception_if_registering_endpoint_with_id_same_as_message_channel_name()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(new OrderService(), 'order')
                    ->withInputChannelName('some')
                    ->withEndpointId('order.register')
            )
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel('order.register'))
            ->process(new ContainerBuilder())
        ;
    }

    public function test_throwing_exception_if_registering_message_channel_name_with_same_name_as_endpoint_id()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel('order.register'))
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(new OrderService(), 'order')
                    ->withInputChannelName('some')
                    ->withEndpointId('order.register')
            )
            ->process(new ContainerBuilder())
        ;
    }

    public function test_throwing_exception_if_registering_endpoint_with_id_same_as_default_message_channel()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerDefaultChannelFor(SimpleMessageChannelBuilder::createDirectMessageChannel('order.register'))
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(new OrderService(), 'order')
                    ->withInputChannelName('some')
                    ->withEndpointId('order.register')
            )
            ->process(new ContainerBuilder())
        ;
    }

    public function test_throwing_exception_if_message_handler_having_same_channel_and_endpoint_id()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(new OrderService(), 'order')
                    ->withInputChannelName('order.register')
                    ->withEndpointId('order.register')
            );
    }

    /**
     * @throws MessagingException
     */
    public function test_registering_interceptors_with_precedence_for_message_handler()
    {
        $inputChannelName = 'inputChannel';
        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), 'sum')
                        ->withInputChannelName($inputChannelName)
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        CalculatingService::create(3),
                        InterfaceToCall::create(CalculatingService::class, 'multiply'),
                        3,
                        CalculatingService::class
                    )
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        CalculatingService::create(2),
                        InterfaceToCall::create(CalculatingService::class, 'sum'),
                        1,
                        CalculatingService::class
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        CalculatingService::create(2),
                        InterfaceToCall::create(CalculatingService::class, 'multiply'),
                        3,
                        CalculatingService::class
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        CalculatingService::create(2),
                        InterfaceToCall::create(CalculatingService::class, 'sum'),
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
            28,
            $outputChannel->receive()->getPayload()
        );
    }

    public function test_registering_single_after_interceptor_for_gateway()
    {
        $requestChannelName = 'inputChannel';

        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create('ref-name', ServiceInterfaceCalculatingService::class, 'calculate', $requestChannelName)
                )
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), 'result')
                        ->withInputChannelName($requestChannelName)
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        CalculatingService::create(3),
                        InterfaceToCall::create(CalculatingService::class, 'sum'),
                        0,
                        ServiceInterfaceCalculatingService::class
                    )
                )
                ->registerConsumerFactory(new EventDrivenConsumerBuilder())
                ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        /** @var ServiceInterfaceCalculatingService $gateway */
        $gateway = $messagingSystemConfiguration->getGatewayByName('ref-name');

        $this->assertEquals(
            4,
            $gateway->calculate(1)
        );
    }

    public function test_registering_interceptors_for_gateway_single_around_interceptor()
    {
        $requestChannelName = 'inputChannel';
        $aroundInterceptor = NoReturnMessageHandler::create();

        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create('ref-name', ServiceInterfaceCalculatingService::class, 'calculate', $requestChannelName)
                )
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), 'result')
                        ->withInputChannelName($requestChannelName)
                )
                ->registerAroundMethodInterceptor(
                    AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(
                        InterfaceToCallRegistry::createEmpty(),
                        $aroundInterceptor,
                        'handle',
                        1,
                        ServiceInterfaceCalculatingService::class
                    )
                )
                ->registerConsumerFactory(new EventDrivenConsumerBuilder())
                ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        /** @var ServiceInterfaceCalculatingService $gateway */
        $gateway = $messagingSystemConfiguration->getGatewayByName('ref-name');

        $this->assertEquals(
            1,
            $gateway->calculate(1)
        );

        $this->assertTrue($aroundInterceptor->wasCalled());
    }

    public function test_registering_interceptors_for_gateway_single_around_and_before_interceptor()
    {
        $requestChannelName = 'inputChannel';
        $aroundInterceptor = NoReturnMessageHandler::create();

        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create('ref-name', ServiceInterfaceCalculatingService::class, 'calculate', $requestChannelName)
                )
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), 'result')
                        ->withInputChannelName($requestChannelName)
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        CalculatingService::create(3),
                        InterfaceToCall::create(CalculatingService::class, 'sum'),
                        1,
                        ServiceInterfaceCalculatingService::class
                    )
                )
                ->registerAroundMethodInterceptor(
                    AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(
                        InterfaceToCallRegistry::createEmpty(),
                        $aroundInterceptor,
                        'handle',
                        1,
                        ServiceInterfaceCalculatingService::class
                    )
                )
                ->registerConsumerFactory(new EventDrivenConsumerBuilder())
                ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        /** @var ServiceInterfaceCalculatingService $gateway */
        $gateway = $messagingSystemConfiguration->getGatewayByName('ref-name');

        $this->assertEquals(
            4,
            $gateway->calculate(1)
        );

        $this->assertTrue($aroundInterceptor->wasCalled());
    }

    public function test_registering_interceptors_for_gateway_using_pointcut()
    {
        $requestChannelName = 'inputChannel';
        $aroundInterceptor = NoReturnMessageHandler::create();

        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create('ref-name', ServiceInterfaceCalculatingService::class, 'calculate', $requestChannelName)
                )
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), 'result')
                        ->withInputChannelName($requestChannelName)
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        CalculatingService::create(3),
                        InterfaceToCall::create(CalculatingService::class, 'multiply'),
                        0,
                        ServiceInterfaceCalculatingService::class
                    )
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        CalculatingService::create(3),
                        InterfaceToCall::create(CalculatingService::class, 'sum'),
                        1,
                        ServiceInterfaceCalculatingService::class
                    )
                )
                ->registerAroundMethodInterceptor(
                    AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(
                        InterfaceToCallRegistry::createEmpty(),
                        $aroundInterceptor,
                        'handle',
                        1,
                        ServiceInterfaceCalculatingService::class
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        CalculatingService::create(0),
                        InterfaceToCall::create(CalculatingService::class, 'result'),
                        1,
                        ServiceInterfaceCalculatingService::class
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        CalculatingService::create(2),
                        InterfaceToCall::create(CalculatingService::class, 'multiply'),
                        0,
                        ServiceInterfaceCalculatingService::class
                    )
                )
                ->registerConsumerFactory(new EventDrivenConsumerBuilder())
                ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        /** @var ServiceInterfaceCalculatingService $gateway */
        $gateway = $messagingSystemConfiguration->getGatewayByName('ref-name');

        $this->assertEquals(
            12,
            $gateway->calculate(1)
        );

        $this->assertTrue($aroundInterceptor->wasCalled());
    }

    public function test_registering_interceptors_for_gateway_using_interceptor_name()
    {
        $requestChannelName = 'inputChannel';
        $aroundInterceptor = NoReturnMessageHandler::create();
        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create('ref-name', ServiceInterfaceCalculatingService::class, 'calculate', $requestChannelName)
                        ->withRequiredInterceptorNames(['interceptor0', 'interceptor1', NoReturnMessageHandler::class, 'interceptor2', 'interceptor3'])
                )
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), 'result')
                        ->withInputChannelName($requestChannelName)
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        CalculatingService::create(3),
                        InterfaceToCall::create(CalculatingService::class, 'multiply'),
                        0,
                        '',
                        name: 'interceptor0'
                    )
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        CalculatingService::create(3),
                        InterfaceToCall::create(CalculatingService::class, 'sum'),
                        1,
                        '',
                        name: 'interceptor1'
                    )
                )
                ->registerAroundMethodInterceptor(
                    AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(
                        InterfaceToCallRegistry::createEmpty(),
                        $aroundInterceptor,
                        'handle',
                        1,
                        ''
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        CalculatingService::create(0),
                        InterfaceToCall::create(CalculatingService::class, 'result'),
                        1,
                        '',
                        name: 'interceptor2'
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        CalculatingService::create(2),
                        InterfaceToCall::create(CalculatingService::class, 'multiply'),
                        0,
                        '',
                        name: 'interceptor3'
                    )
                )
                ->registerConsumerFactory(new EventDrivenConsumerBuilder())
                ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        /** @var ServiceInterfaceCalculatingService $gateway */
        $gateway = $messagingSystemConfiguration->getGatewayByName('ref-name');

        $this->assertEquals(
            12,
            $gateway->calculate(1)
        );
        $this->assertTrue($aroundInterceptor->wasCalled());
    }

    /**
     * @throws MessagingException
     */
    public function test_throwing_exception_if_registering_handlers_with_same_endpoint_id()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerMessageHandler(DumbMessageHandlerBuilder::createSimple()->withEndpointId('1'))
            ->registerMessageHandler(DumbMessageHandlerBuilder::createSimple()->withEndpointId('1'));
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function test_generating_random_id_if_no_endpoint_id_passed()
    {
        MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerMessageHandler(DumbMessageHandlerBuilder::createSimple())
            ->registerMessageHandler(DumbMessageHandlerBuilder::createSimple());

        $this->assertTrue(true);
    }

    /**
     * @throws MessagingException
     */
    public function test_throwing_exception_if_trying_to_register_two_channels_with_same_names()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel('some'))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel('some'));
    }

    public function test_calling_console_command_with_default()
    {
        $consoleCommandName = 'someName';
        $channelName = MessagingEntrypoint::ENTRYPOINT;
        $queueChannel = QueueChannel::create();
        $consoleCommand = ConsoleCommandConfiguration::create($channelName, $consoleCommandName, [
            ConsoleCommandParameter::create('id', 'header.id', false),
            ConsoleCommandParameter::createWithDefaultValue('token', 'header.token', false, false, 123),
        ]);

        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerGatewayBuilder(GatewayProxyBuilder::create(MessagingEntrypoint::class, MessagingEntrypoint::class, 'sendWithHeaders', $channelName))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(MessagingEntrypointWithHeadersPropagation::class, MessagingEntrypointWithHeadersPropagation::class, 'sendWithHeaders', $channelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($channelName, $queueChannel))
            ->registerConsoleCommand($consoleCommand)
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $configuredMessagingSystem->runConsoleCommand($consoleCommandName, ['id' => 1]);

        $headers = $queueChannel->receive()->getHeaders()->headers();
        $this->assertEquals(1, $headers['header.id']);
        $this->assertEquals(123, $headers['header.token']);
    }

    public function test_calling_console_command_overriding_default_parameter()
    {
        $consoleCommandName = 'someName';
        $channelName = MessagingEntrypoint::ENTRYPOINT;
        $queueChannel = QueueChannel::create();
        $consoleCommand = ConsoleCommandConfiguration::create($channelName, $consoleCommandName, [
            ConsoleCommandParameter::create('id', 'header.id', false),
            ConsoleCommandParameter::createWithDefaultValue('token', 'header.token', false, false, 123),
        ]);

        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
            ->registerGatewayBuilder(GatewayProxyBuilder::create(MessagingEntrypoint::class, MessagingEntrypoint::class, 'sendWithHeaders', $channelName))
            ->registerGatewayBuilder(GatewayProxyBuilder::create(MessagingEntrypointWithHeadersPropagation::class, MessagingEntrypointWithHeadersPropagation::class, 'sendWithHeaders', $channelName))
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($channelName, $queueChannel))
            ->registerConsoleCommand($consoleCommand)
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $configuredMessagingSystem->runConsoleCommand($consoleCommandName, ['id' => 1, 'token' => 1000]);

        $headers = $queueChannel->receive()->getHeaders()->headers();
        $this->assertEquals(1, $headers['header.id']);
        $this->assertEquals(1000, $headers['header.token']);
    }

    public function test_throwing_exception_if_registered_command_handler_with_same_routing_as_asynchronous_channel()
    {
        $this->expectException(InvalidArgumentException::class);

        EcotoneLite::bootstrapForTesting(
            [SomeTestCommandHandler::class],
            [new SomeTestCommandHandler()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel('input'),
                ])
        );
    }

    public function test_throwing_exception_if_registered_event_handler_with_same_routing_as_asynchronous_channel()
    {
        $this->expectException(InvalidArgumentException::class);

        EcotoneLite::bootstrapForTesting(
            [SomeTestEventHandler::class],
            [new SomeTestEventHandler()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withExtensionObjects([
                    SimpleMessageChannelBuilder::createQueueChannel('input'),
                ])
        );
    }

    public function test_throwing_exception_on_lacking_request_channel_for_gateway(): void
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(
            [MultipleMethodsGatewayExample::class],
            [],
        );
    }

    public function test_throwing_exception_on_lacking_output_channel_for_endpoint(): void
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(
            [BridgeExampleIncomplete::class],
            [],
        );
    }
}
