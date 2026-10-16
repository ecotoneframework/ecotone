<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config;

use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Interceptor\Precedence;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageChannel;
use Ecotone\Api\Messaging\PollableChannel;
use Ecotone\Messaging\Channel\MessageChannelInterceptorAdapter;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Channel\SimpleChannelInterceptorBuilder;
use Ecotone\Messaging\Config\BootstrapConfiguration;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ConsoleCommandConfiguration;
use Ecotone\Messaging\Config\ConsoleCommandParameter;
use Ecotone\Messaging\Config\Container\ContainerBuilder;
use Ecotone\Messaging\Config\Container\GatewayProxyReference;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ServiceCacheConfiguration;
use Ecotone\Messaging\Endpoint\EventDriven\EventDrivenConsumerBuilder;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use Ecotone\Messaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use Ecotone\Messaging\Gateway\MessagingEntrypointService;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptorBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessagingException;
use Ecotone\Test\StaticMessageBuilder;
use Exception;
use InvalidArgumentException;
use Test\Ecotone\Messaging\Fixture\Annotation\Interceptor\CalculatingServiceInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Gateway\CombinedGatewayExample;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Gateway\SingleMethodGatewayExample;
use Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling\DeadLetter\OrderService;
use Test\Ecotone\Messaging\Fixture\Endpoint\ConsumerContinuouslyWorkingService;
use Test\Ecotone\Messaging\Fixture\Handler\DumbMessageHandlerBuilder;
use Test\Ecotone\Messaging\Fixture\Handler\ExceptionMessageHandler;
use Test\Ecotone\Messaging\Fixture\Handler\Gateway\MultipleMethodsGatewayExample;
use Test\Ecotone\Messaging\Fixture\Handler\NoReturnMessageHandler;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\CallWithAnnotationFromMethodInterceptorExample;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\StubCallSavingService;
use Test\Ecotone\Messaging\Fixture\SameChannelAndRouting\SomeTestCommandHandler;
use Test\Ecotone\Messaging\Fixture\SameChannelAndRouting\SomeTestEventHandler;
use Test\Ecotone\Messaging\Fixture\Service\CalculatingService;
use Test\Ecotone\Messaging\Fixture\Service\CalculatingServiceForAsynchronousScenario;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceCalculatingService;
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
    private function createMessagingSystemConfiguration(): Configuration
    {
        return MessagingSystemConfiguration::prepareWithDefaultsForTesting();
    }

    /**
     * @throws ConfigurationException
     * @throws Exception
     * @throws MessagingException
     */
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

        $messagingSystem->getMessageChannelByName($messageChannelName)->send(StaticMessageBuilder::withPayload('a')->build());

        $messagingSystem->run('executor', ExecutionPollingMetadata::createWithDefaults()->withExecutionTimeLimitInMilliseconds(1));

        $this->assertTrue($messageHandler->wasCalled());
    }

    public function test_throwing_exception_if_registered_asynchronous_for_not_existing_endpoint()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepareWithDefaultsForTesting()
            ->registerAsynchronousEndpoint('asyncChannel', 'endpointId')
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());
    }

    public function test_registering_before_call_intercepted_asynchronous_endpoint()
    {
        $reference = 'calculatingService';
        $calculatingService = CalculatingService::create(1);
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaultsForTesting()
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageHandler(
                ServiceActivatorBuilder::create($reference, InterfaceToCall::create(CalculatingService::class, 'result'))
                    ->withEndpointId('endpointId')
                    ->withInputChannelName('inputChannel')
            )
            ->registerAsynchronousEndpoint('asyncChannel', 'endpointId')
            ->registerBeforeMethodInterceptor(MethodInterceptorBuilder::create(
                Reference::to($reference),
                InterfaceToCall::create(CalculatingService::class, 'sum'),
                1,
                CalculatingService::class
            ))
            ->registerPollingMetadata(PollingMetadata::create('asyncChannel')->withExecutionAmountLimit(1))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('asyncChannel'))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
                $reference => $calculatingService,
            ]));

        $message = StaticMessageBuilder::withPayload(2)
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
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('asyncOne'),
                SimpleMessageChannelBuilder::createQueueChannel('asyncTwo'),
            ],
            enabledModules: [],
        );

        $ecotoneLite->sendCommandWithRouting('getResultOne', 2);
        $ecotoneLite->run('asyncOne');
        $this->assertEquals(4, $calculatingService->getLastResult());

        $ecotoneLite->sendCommandWithRouting('getResultTwo', 3);
        $ecotoneLite->run('asyncTwo');
        $this->assertEquals(6, $calculatingService->getLastResult());
    }

    public function test_registering_before_call_intercepted_asynchronous_endpoint_with_two_channels()
    {
        $reference = 'calculatingService';
        $calculatingService = CalculatingService::create(1);
        $asyncChannelNameOne = 'asyncChannelOne';
        $asyncChannelNameTwo = 'asyncChannelTwo';
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaultsForTesting()
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageHandler(
                ServiceActivatorBuilder::create($reference, InterfaceToCall::create(CalculatingService::class, 'result'))
                    ->withEndpointId('endpointId')
                    ->withInputChannelName('inputChannel')
            )
            ->registerAsynchronousEndpoint([$asyncChannelNameOne, $asyncChannelNameTwo], 'endpointId')
            ->registerBeforeMethodInterceptor(MethodInterceptorBuilder::create(
                Reference::to($reference),
                InterfaceToCall::create(CalculatingService::class, 'sum'),
                1,
                CalculatingService::class
            ))
            ->registerPollingMetadata(PollingMetadata::create($asyncChannelNameOne)->withTestingSetup())
            ->registerPollingMetadata(PollingMetadata::create($asyncChannelNameTwo)->withTestingSetup())
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($asyncChannelNameOne))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel($asyncChannelNameTwo))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
                $reference => $calculatingService,
            ]));

        /** @var MessageChannel $channel */
        $channel = $configuredMessagingSystem->getMessageChannelByName('inputChannel');

        $channel->send(StaticMessageBuilder::withPayload(2)->build());
        $this->assertNull($calculatingService->getLastResult());
        $configuredMessagingSystem->run($asyncChannelNameOne);
        $this->assertNull($calculatingService->getLastResult());
        $configuredMessagingSystem->run($asyncChannelNameTwo);
        $this->assertEquals(3, $calculatingService->getLastResult());
    }

    public function test_throwing_exception_if_register_polling_metadata_for_non_existing_endpoint()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepareWithDefaultsForTesting()
            ->registerPollingMetadata(PollingMetadata::create('some'))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());
    }

    public function test_registering_presend_interceptor_wish_async_channel()
    {
        $reference = 'calculatingService';
        $calculatingService = CalculatingService::create(1);
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaultsForTesting()
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
                    Reference::to($reference),
                    InterfaceToCall::create(CalculatingService::class, 'sum'),
                    1,
                    CalculatingService::class
                )
            )
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
                $reference => $calculatingService,
            ]));

        $replyChannel = QueueChannel::create();
        $requestMessage = StaticMessageBuilder::withPayload(2)
            ->setReplyChannel($replyChannel)
            ->build();

        /** @var PollableChannel $channel */
        $channel = $configuredMessagingSystem->getMessageChannelByName('inputChannel');

        $channel->send($requestMessage);

        $this->assertEquals(3, $configuredMessagingSystem->getMessageChannelByName('asyncChannel')->receive()->getPayload());
    }

    public function test_registering_presend_interceptor_wish_two_async_channels()
    {
        $reference = 'calculatingService';
        $interceptorService = CalculatingService::create(1);
        $asyncChannelOne = 'asyncChannelOne';
        $asyncChannelTwo = 'asyncChannelTwo';
        $calculatingService = CalculatingService::create(0);
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaultsForTesting()
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
                    Reference::to($reference),
                    InterfaceToCall::create(CalculatingService::class, 'sum'),
                    1,
                    CalculatingService::class
                )
            )
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
                $reference => $interceptorService,
            ]));

        /** @var PollableChannel $channel */
        $channel = $configuredMessagingSystem->getMessageChannelByName('inputChannel');

        $channel->send(StaticMessageBuilder::withPayload(2)->build());
        $this->assertNull($calculatingService->getLastResult());
        $this->assertEquals(3, $configuredMessagingSystem->getMessageChannelByName($asyncChannelOne)->receive()->getPayload());
    }

    public function test_registering_presend_interceptor_polling_channel()
    {
        $reference = 'calculatingService';
        $calculatingService = CalculatingService::create(1);
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaultsForTesting()
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
                    Reference::to($reference),
                    InterfaceToCall::create(CalculatingService::class, 'sum'),
                    1,
                    CalculatingService::class
                )
            )
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
                $reference => $calculatingService,
            ]));

        $replyChannel = QueueChannel::create();
        $requestMessage = StaticMessageBuilder::withPayload(2)
            ->setReplyChannel($replyChannel)
            ->build();

        /** @var PollableChannel $channel */
        $channel = $configuredMessagingSystem->getMessageChannelByName('inputChannel');

        $channel->send($requestMessage);

        $this->assertEquals(3, $configuredMessagingSystem->getMessageChannelByName('inputChannel')->receive()->getPayload());
    }

    public function test_registering_multiple_before_send_interceptors()
    {
        $reference1 = 'calculatingService1';
        $reference2 = 'calculatingService2';
        $calculatingService1 = CalculatingService::create(1);
        $calculatingService2 = CalculatingService::create(1);
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaultsForTesting()
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
                    Reference::to($reference1),
                    InterfaceToCall::create(CalculatingService::class, 'sum'),
                    1,
                    CalculatingService::class
                )
            )
            ->registerBeforeSendInterceptor(
                MethodInterceptorBuilder::create(
                    Reference::to($reference2),
                    InterfaceToCall::create(CalculatingService::class, 'sum'),
                    1,
                    CalculatingService::class
                )
            )
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
                $reference1 => $calculatingService1,
                $reference2 => $calculatingService2,
            ]));

        $replyChannel = QueueChannel::create();
        $requestMessage = StaticMessageBuilder::withPayload(2)
            ->setReplyChannel($replyChannel)
            ->build();

        /** @var PollableChannel $channel */
        $channel = $configuredMessagingSystem->getMessageChannelByName('inputChannel1');

        $channel->send($requestMessage);

        $this->assertEquals(4, $configuredMessagingSystem->getMessageChannelByName('inputChannel1')->receive()->getPayload());
    }

    public function test_not_duplicating_before_send_interceptors()
    {
        $reference = 'calculatingService';
        $calculatingService = CalculatingService::create(1);
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaultsForTesting()
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
                    Reference::to($reference),
                    InterfaceToCall::create(CalculatingService::class, 'sum'),
                    1,
                    CalculatingService::class
                )
            )
            ->registerBeforeSendInterceptor(
                MethodInterceptorBuilder::create(
                    Reference::to($reference),
                    InterfaceToCall::create(CalculatingService::class, 'sum'),
                    1,
                    CalculatingService::class
                )
            )
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
                $reference => $calculatingService,
            ]));

        $replyChannel = QueueChannel::create();
        $requestMessage = StaticMessageBuilder::withPayload(2)
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
        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaultsForTesting()
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

        $message = StaticMessageBuilder::withPayload(2)
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

        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaultsForTesting()
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference($calculatingService, 'result')
                    ->withEndpointId('endpointId')
                    ->withInputChannelName('inputChannel')
            )
            ->registerAsynchronousEndpoint('asyncChannel', 'endpointId')
            ->registerPollingMetadata(PollingMetadata::create('asyncChannel')->withExecutionAmountLimit(1))
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('asyncChannel'))
            ->registerChannelInterceptor(SimpleChannelInterceptorBuilder::create('inputChannel', 'ref-name'))
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith(['ref-name' => $channelInterceptor]));

        $replyChannel = QueueChannel::create();
        $requestMessage = StaticMessageBuilder::withPayload(2)
            ->setReplyChannel($replyChannel)
            ->build();

        /** @var MessageChannel $channel */
        $channel = $configuredMessagingSystem->getMessageChannelByName('inputChannel');

        $channel->send($requestMessage);

        $this->assertTrue($channelInterceptor->wasPreSendCalled());
        $this->assertSame($requestMessage, $channelInterceptor->getCapturedMessage());
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function test_intercepting_channel_before_sending()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaultsForTesting();

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

        $message = StaticMessageBuilder::withPayload('testMessage')->build();
        /** @var QueueChannel|MessageChannelInterceptorAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName);

        $preSendModifiedMessage = StaticMessageBuilder::withPayload('preSend')->build();
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
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaultsForTesting();

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

        $message = StaticMessageBuilder::withPayload('testMessage')->build();
        $messageFirstModification = StaticMessageBuilder::withPayload('preSend1')->build();
        $messageSecondModification = StaticMessageBuilder::withPayload('preSend2')->build();

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
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaultsForTesting();

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

        $message = StaticMessageBuilder::withPayload('testMessage')->build();
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
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaultsForTesting();

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

        $message = StaticMessageBuilder::withPayload('testMessage')->build();
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
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaultsForTesting();

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

        $message = StaticMessageBuilder::withPayload('testMessage')->build();
        /** @var QueueChannel|MessageChannelInterceptorAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName);

        $queueChannel->send($message);

        $this->assertTrue($channelInterceptor->wasPreSendCalled());
        $this->assertTrue($channelInterceptor->wasAfterSendCompletionCalled());
        $this->assertSame($message, $channelInterceptor->getCapturedMessage());
        $this->assertInstanceOf(InvalidArgumentException::class, $channelInterceptor->getCapturedException());
    }

    /**
     * @throws ConfigurationException
     * @throws MessagingException
     */
    public function test_intercepting_with_multiple_channels()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaultsForTesting();

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

        $message = StaticMessageBuilder::withPayload('testMessage')->build();
        /** @var QueueChannel|MessageChannelInterceptorAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName2);

        $preSendModifiedMessage = StaticMessageBuilder::withPayload('preSend')->build();
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
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaultsForTesting();

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

        $message = StaticMessageBuilder::withPayload('testMessage')->build();
        /** @var QueueChannel|MessageChannelInterceptorAdapter $queueChannel */
        $queueChannel = $messagingSystem->getMessageChannelByName($messageChannelName);

        $preSendModifiedMessage = StaticMessageBuilder::withPayload('preSend')->build();
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
    public function test_throwing_exception_if_registering_inbound_channel_adapter_with_same_names()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaultsForTesting();
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
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaultsForTesting();
        $requestChannelName = 'requestChannelName';
        $endpointName = 'pollableName';

        $beforeInterceptorRef = 'beforeInterceptor';
        $afterInterceptorRef = 'afterInterceptor';
        $lastServiceRef = 'lastService';

        $beforeInterceptor = CalculatingService::create(2);
        $afterInterceptor = CalculatingService::create(2);
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
                    Reference::to($beforeInterceptorRef),
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
                    Reference::to($afterInterceptorRef),
                    InterfaceToCall::create(CalculatingService::class, 'multiply'),
                    1,
                    ConsumerContinuouslyWorkingService::class
                )
            )
            ->registerAfterMethodInterceptor(
                MethodInterceptorBuilder::create(
                    Reference::to($lastServiceRef),
                    InterfaceToCall::create(CalculatingService::class, 'result'),
                    1,
                    ConsumerContinuouslyWorkingService::class
                )
            )
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
                $beforeInterceptorRef => $beforeInterceptor,
                $afterInterceptorRef => $afterInterceptor,
                $lastServiceRef => $lastServiceFromChain,
            ]));

        $messagingSystem->run($endpointName);

        $this->assertEquals(26, $lastServiceFromChain->getLastResult());
    }

    public function test_intercepting_channel_adapter_with_void_services_by_passing_through_message()
    {
        $messagingSystemConfiguration = MessagingSystemConfiguration::prepareWithDefaultsForTesting();
        $requestChannelName = 'requestChannelName';
        $endpointName = 'pollableName';
        $interceptingHandler = NoReturnMessageHandler::create();

        $messagingSystem = $messagingSystemConfiguration
            ->registerConsumer(
                InboundChannelAdapterBuilder::create(
                    $requestChannelName,
                    ConsumerContinuouslyWorkingService::class,
                    InterfaceToCall::create(ConsumerContinuouslyWorkingService::class, 'executeReturn')
                )->withEndpointId($endpointName)
            )
            ->registerMessageHandler(ServiceActivatorBuilder::createWithDirectReference(ServiceWithReturnValue::create(), 'getName')->withInputChannelName($requestChannelName))
            ->registerConsumerFactory(new EventDrivenConsumerBuilder())
            ->registerPollingMetadata(
                PollingMetadata::create($endpointName)
                    ->withHandledMessageLimit(1)
            )
            ->registerBeforeMethodInterceptor(
                MethodInterceptorBuilder::create(
                    Reference::to(NoReturnMessageHandler::class),
                    InterfaceToCall::create(NoReturnMessageHandler::class, 'handle'),
                    1,
                    ConsumerContinuouslyWorkingService::class
                )
            )
            ->registerAroundMethodInterceptor(
                AroundInterceptorBuilder::create(
                    NoReturnMessageHandler::class,
                    InterfaceToCall::create(NoReturnMessageHandler::class, 'handle'),
                    1,
                    ConsumerContinuouslyWorkingService::class
                )
            )
            ->registerAfterMethodInterceptor(
                MethodInterceptorBuilder::create(
                    Reference::to(NoReturnMessageHandler::class),
                    InterfaceToCall::create(NoReturnMessageHandler::class, 'handle'),
                    1,
                    ConsumerContinuouslyWorkingService::class
                )
            )
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
                ConsumerContinuouslyWorkingService::class => ConsumerContinuouslyWorkingService::createWithReturn(5),
                NoReturnMessageHandler::class => $interceptingHandler,
            ]));

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
        $beforeInterceptorRef = 'beforeInterceptor';
        $afterInterceptorRef = 'afterInterceptor';

        $beforeInterceptor = CalculatingService::create(2);
        $afterInterceptor = CalculatingService::create(3);

        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepareWithDefaultsForTesting()
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(1), 'sum')
                        ->withInputChannelName($inputChannelName)
                        ->withEndpointId($endpointName)
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        Reference::to($beforeInterceptorRef),
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
                        Reference::to($afterInterceptorRef),
                        InterfaceToCall::create(CalculatingService::class, 'multiply'),
                        Precedence::DEFAULT_PRECEDENCE,
                        CalculatingService::class
                    )
                )
                ->registerConsumerFactory(new EventDrivenConsumerBuilder())
                ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
                    $beforeInterceptorRef => $beforeInterceptor,
                    $afterInterceptorRef => $afterInterceptor,
                ]));

        $messageChannel = $messagingSystemConfiguration->getMessageChannelByName($inputChannelName);
        $outputChannel = QueueChannel::create();

        $messageChannel->send(
            StaticMessageBuilder::withPayload(0)
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
            MessagingSystemConfiguration::prepareWithDefaultsForTesting()
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

        $message = StaticMessageBuilder::withPayload(0)
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
                BootstrapConfiguration::createWithDefaults(),
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
            StaticMessageBuilder::withPayload(0)
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

        $messagingSystem->getMessageChannelByName('replyChannel')->send(StaticMessageBuilder::withPayload('some')->build());

        $combinedGateway = $messagingSystem
            ->getNonProxyGatewayByName($buyGateway->getProxyMethodReference());

        $this->assertEquals('some', $combinedGateway->execute([]));
        $this->assertNotNull($messagingSystem->getMessageChannelByName('buy')->receive());
        $this->assertContains('combinedGateway', array_map(fn (GatewayProxyReference $gatewayProxyReference) => $gatewayProxyReference->getReferenceName(), $messagingSystem->getGatewayList()));
    }

    public function test_throwing_exception_if_registering_message_channel_name_with_same_name_as_endpoint_id()
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepareWithDefaultsForTesting()
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

        MessagingSystemConfiguration::prepareWithDefaultsForTesting()
            ->registerDefaultChannelFor(SimpleMessageChannelBuilder::createDirectMessageChannel('order.register'))
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(new OrderService(), 'order')
                    ->withInputChannelName('some')
                    ->withEndpointId('order.register')
            )
            ->process(new ContainerBuilder())
        ;
    }

    /**
     * @throws MessagingException
     */
    public function test_registering_interceptors_with_precedence_for_message_handler()
    {
        $inputChannelName = 'inputChannel';
        $beforeInterceptor1Ref = 'beforeInterceptor1';
        $beforeInterceptor2Ref = 'beforeInterceptor2';
        $afterInterceptor1Ref = 'afterInterceptor1';
        $afterInterceptor2Ref = 'afterInterceptor2';

        $beforeInterceptor1 = CalculatingService::create(3);
        $beforeInterceptor2 = CalculatingService::create(2);
        $afterInterceptor1 = CalculatingService::create(2);
        $afterInterceptor2 = CalculatingService::create(2);

        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepareWithDefaultsForTesting()
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), 'sum')
                        ->withInputChannelName($inputChannelName)
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        Reference::to($beforeInterceptor1Ref),
                        InterfaceToCall::create(CalculatingService::class, 'multiply'),
                        3,
                        CalculatingService::class
                    )
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        Reference::to($beforeInterceptor2Ref),
                        InterfaceToCall::create(CalculatingService::class, 'sum'),
                        1,
                        CalculatingService::class
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        Reference::to($afterInterceptor1Ref),
                        InterfaceToCall::create(CalculatingService::class, 'multiply'),
                        3,
                        CalculatingService::class
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        Reference::to($afterInterceptor2Ref),
                        InterfaceToCall::create(CalculatingService::class, 'sum'),
                        1,
                        CalculatingService::class
                    )
                )
                ->registerConsumerFactory(new EventDrivenConsumerBuilder())
                ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
                    $beforeInterceptor1Ref => $beforeInterceptor1,
                    $beforeInterceptor2Ref => $beforeInterceptor2,
                    $afterInterceptor1Ref => $afterInterceptor1,
                    $afterInterceptor2Ref => $afterInterceptor2,
                ]));

        $messageChannel = $messagingSystemConfiguration->getMessageChannelByName($inputChannelName);
        $outputChannel = QueueChannel::create();

        $messageChannel->send(
            StaticMessageBuilder::withPayload(2)
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
        $interceptorRef = 'interceptor';
        $interceptor = CalculatingService::create(3);

        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepareWithDefaultsForTesting()
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create('ref-name', ServiceInterfaceCalculatingService::class, 'calculate', $requestChannelName)
                )
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), 'result')
                        ->withInputChannelName($requestChannelName)
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        Reference::to($interceptorRef),
                        InterfaceToCall::create(CalculatingService::class, 'sum'),
                        0,
                        ServiceInterfaceCalculatingService::class
                    )
                )
                ->registerConsumerFactory(new EventDrivenConsumerBuilder())
                ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
                    $interceptorRef => $interceptor,
                ]));

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
            MessagingSystemConfiguration::prepareWithDefaultsForTesting()
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create('ref-name', ServiceInterfaceCalculatingService::class, 'calculate', $requestChannelName)
                )
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), 'result')
                        ->withInputChannelName($requestChannelName)
                )
                ->registerAroundMethodInterceptor(
                    AroundInterceptorBuilder::create(
                        NoReturnMessageHandler::class,
                        InterfaceToCall::create(NoReturnMessageHandler::class, 'handle'),
                        1,
                        ServiceInterfaceCalculatingService::class
                    )
                )
                ->registerConsumerFactory(new EventDrivenConsumerBuilder())
                ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
                    NoReturnMessageHandler::class => $aroundInterceptor,
                ]));

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
        $beforeInterceptorRef = 'beforeInterceptor';
        $beforeInterceptor = CalculatingService::create(3);

        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepareWithDefaultsForTesting()
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create('ref-name', ServiceInterfaceCalculatingService::class, 'calculate', $requestChannelName)
                )
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), 'result')
                        ->withInputChannelName($requestChannelName)
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        Reference::to($beforeInterceptorRef),
                        InterfaceToCall::create(CalculatingService::class, 'sum'),
                        1,
                        ServiceInterfaceCalculatingService::class
                    )
                )
                ->registerAroundMethodInterceptor(
                    AroundInterceptorBuilder::create(
                        NoReturnMessageHandler::class,
                        InterfaceToCall::create(NoReturnMessageHandler::class, 'handle'),
                        1,
                        ServiceInterfaceCalculatingService::class
                    )
                )
                ->registerConsumerFactory(new EventDrivenConsumerBuilder())
                ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
                    $beforeInterceptorRef => $beforeInterceptor,
                    NoReturnMessageHandler::class => $aroundInterceptor,
                ]));

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

        $beforeInterceptor1Ref = 'beforeInterceptor1';
        $beforeInterceptor2Ref = 'beforeInterceptor2';
        $afterInterceptor1Ref = 'afterInterceptor1';
        $afterInterceptor2Ref = 'afterInterceptor2';

        $beforeInterceptor1 = CalculatingService::create(3);
        $beforeInterceptor2 = CalculatingService::create(3);
        $afterInterceptor1 = CalculatingService::create(0);
        $afterInterceptor2 = CalculatingService::create(2);

        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepareWithDefaultsForTesting()
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create('ref-name', ServiceInterfaceCalculatingService::class, 'calculate', $requestChannelName)
                )
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), 'result')
                        ->withInputChannelName($requestChannelName)
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        Reference::to($beforeInterceptor1Ref),
                        InterfaceToCall::create(CalculatingService::class, 'multiply'),
                        0,
                        ServiceInterfaceCalculatingService::class
                    )
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        Reference::to($beforeInterceptor2Ref),
                        InterfaceToCall::create(CalculatingService::class, 'sum'),
                        1,
                        ServiceInterfaceCalculatingService::class
                    )
                )
                ->registerAroundMethodInterceptor(
                    AroundInterceptorBuilder::create(
                        NoReturnMessageHandler::class,
                        InterfaceToCall::create(NoReturnMessageHandler::class, 'handle'),
                        1,
                        ServiceInterfaceCalculatingService::class
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        Reference::to($afterInterceptor1Ref),
                        InterfaceToCall::create(CalculatingService::class, 'result'),
                        1,
                        ServiceInterfaceCalculatingService::class
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        Reference::to($afterInterceptor2Ref),
                        InterfaceToCall::create(CalculatingService::class, 'multiply'),
                        0,
                        ServiceInterfaceCalculatingService::class
                    )
                )
                ->registerConsumerFactory(new EventDrivenConsumerBuilder())
                ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
                    $beforeInterceptor1Ref => $beforeInterceptor1,
                    $beforeInterceptor2Ref => $beforeInterceptor2,
                    $afterInterceptor1Ref => $afterInterceptor1,
                    $afterInterceptor2Ref => $afterInterceptor2,
                    NoReturnMessageHandler::class => $aroundInterceptor,
                ]));

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

        $interceptor0Ref = 'interceptor0Service';
        $interceptor1Ref = 'interceptor1Service';
        $interceptor2Ref = 'interceptor2Service';
        $interceptor3Ref = 'interceptor3Service';

        $interceptor0 = CalculatingService::create(3);
        $interceptor1 = CalculatingService::create(3);
        $interceptor2 = CalculatingService::create(0);
        $interceptor3 = CalculatingService::create(2);

        $messagingSystemConfiguration =
            MessagingSystemConfiguration::prepareWithDefaultsForTesting()
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
                        Reference::to($interceptor0Ref),
                        InterfaceToCall::create(CalculatingService::class, 'multiply'),
                        0,
                        '',
                        name: 'interceptor0'
                    )
                )
                ->registerBeforeMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        Reference::to($interceptor1Ref),
                        InterfaceToCall::create(CalculatingService::class, 'sum'),
                        1,
                        '',
                        name: 'interceptor1'
                    )
                )
                ->registerAroundMethodInterceptor(
                    AroundInterceptorBuilder::create(
                        NoReturnMessageHandler::class,
                        InterfaceToCall::create(NoReturnMessageHandler::class, 'handle'),
                        1,
                        ''
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        Reference::to($interceptor2Ref),
                        InterfaceToCall::create(CalculatingService::class, 'result'),
                        1,
                        '',
                        name: 'interceptor2'
                    )
                )
                ->registerAfterMethodInterceptor(
                    MethodInterceptorBuilder::create(
                        Reference::to($interceptor3Ref),
                        InterfaceToCall::create(CalculatingService::class, 'multiply'),
                        0,
                        '',
                        name: 'interceptor3'
                    )
                )
                ->registerConsumerFactory(new EventDrivenConsumerBuilder())
                ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createWith([
                    $interceptor0Ref => $interceptor0,
                    $interceptor1Ref => $interceptor1,
                    $interceptor2Ref => $interceptor2,
                    $interceptor3Ref => $interceptor3,
                    NoReturnMessageHandler::class => $aroundInterceptor,
                ]));

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
    public function test_calling_console_command_with_default()
    {
        $consoleCommandName = 'someName';
        $channelName = MessagingEntrypointService::ENTRYPOINT;
        $queueChannel = QueueChannel::create();
        $consoleCommand = ConsoleCommandConfiguration::create($channelName, $consoleCommandName, [
            ConsoleCommandParameter::create('id', 'header.id', false),
            ConsoleCommandParameter::createWithDefaultValue('token', 'header.token', false, false, 123),
        ]);

        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaultsForTesting()
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
        $channelName = MessagingEntrypointService::ENTRYPOINT;
        $queueChannel = QueueChannel::create();
        $consoleCommand = ConsoleCommandConfiguration::create($channelName, $consoleCommandName, [
            ConsoleCommandParameter::create('id', 'header.id', false),
            ConsoleCommandParameter::createWithDefaultValue('token', 'header.token', false, false, 123),
        ]);

        $configuredMessagingSystem = MessagingSystemConfiguration::prepareWithDefaultsForTesting()
            ->registerMessageChannel(SimpleMessageChannelBuilder::create($channelName, $queueChannel))
            ->registerConsoleCommand($consoleCommand)
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $configuredMessagingSystem->runConsoleCommand($consoleCommandName, ['id' => 1, 'token' => 1000]);

        $headers = $queueChannel->receive()->getHeaders()->headers();
        $this->assertEquals(1, $headers['header.id']);
        $this->assertEquals(1000, $headers['header.token']);
    }

    public function test_command_handler_routing_key_can_match_asynchronous_channel_name(): void
    {
        $commandHandler = new SomeTestCommandHandler();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [SomeTestCommandHandler::class],
            [$commandHandler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('orders')],
            enabledModules: [],
        );

        $ecotone->sendCommandWithRouting('orders');
        $ecotone->run('orders', ExecutionPollingMetadata::createWithTestingSetup(1));

        $this->assertSame(1, $commandHandler->handled);
    }

    public function test_event_handler_routing_key_can_match_asynchronous_channel_name(): void
    {
        $eventHandler = new SomeTestEventHandler();

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [SomeTestEventHandler::class],
            [$eventHandler],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('orders')],
            enabledModules: [],
        );

        $ecotone->publishEventWithRouting('orders');
        $ecotone->run('orders', ExecutionPollingMetadata::createWithTestingSetup(1));

        $this->assertSame(1, $eventHandler->handled);
    }

    public function test_throwing_exception_on_lacking_request_channel_for_gateway(): void
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(
            [MultipleMethodsGatewayExample::class],
            [],
            enabledModules: [],
        );
    }

    public function test_throwing_exception_on_lacking_output_channel_for_endpoint(): void
    {
        $this->expectException(ConfigurationException::class);

        MessagingSystemConfiguration::prepareWithDefaultsForTesting()
            ->registerMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference(CalculatingService::create(0), 'result')
                    ->withInputChannelName('bridgeExample')
                    ->withOutputMessageChannel('bridgeSum')
            )
            ->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());
    }
}
