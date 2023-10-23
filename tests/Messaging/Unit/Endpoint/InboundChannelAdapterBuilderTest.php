<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Endpoint;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Endpoint\EndpointRunner;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use Ecotone\Messaging\Endpoint\NullAcknowledgementCallback;
use Ecotone\Messaging\Endpoint\PollingConsumer\InterceptedConsumerRunner;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagePoller;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Messaging\Transaction\Null\NullTransaction;
use Ecotone\Messaging\Transaction\Null\NullTransactionFactory;
use Ecotone\Messaging\Transaction\Transactional;
use Ecotone\Messaging\Transaction\TransactionInterceptor;
use Ecotone\Test\ComponentTestBuilder;
use Test\Ecotone\Messaging\Fixture\Endpoint\ConsumerContinuouslyWorkingService;
use Test\Ecotone\Messaging\Fixture\Endpoint\ConsumerStoppingService;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingNoArguments;
use Test\Ecotone\Messaging\Fixture\Service\ServiceExpectingOneArgument;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class InboundChannelAdapterBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
 */
class InboundChannelAdapterBuilderTest extends MessagingTest
{
    /**
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_throwing_exception_if_passed_reference_service_has_parameters()
    {
        $this->expectException(InvalidArgumentException::class);
        ComponentTestBuilder::create()
            ->withReference('someRef', ServiceExpectingOneArgument::create())
            ->withPollingMetadata(PollingMetadata::create('test'))
            ->build(
                InboundChannelAdapterBuilder::create(
                    'channelName',
                    'someRef',
                    InterfaceToCall::create(ServiceExpectingOneArgument::class, 'withReturnValue')
                )
                ->withEndpointId('test')
            );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_passed_reference_should_return_parameters()
    {
        $this->expectException(InvalidArgumentException::class);
        ComponentTestBuilder::create()
            ->withReference('someRef', ServiceExpectingOneArgument::create())
            ->withPollingMetadata(PollingMetadata::create('test'))
            ->build(
                InboundChannelAdapterBuilder::create(
                    'channelName',
                    'someRef',
                    InterfaceToCall::create(ServiceExpectingNoArguments::class, 'withoutReturnValue')
                )->withEndpointId('test')
            );
    }

    public function test_executing_with_no_parameters_when_null_channel_defined()
    {
        $inputChannelName = NullableMessageChannel::CHANNEL_NAME;
        $service = ServiceExpectingNoArguments::create();

        /** @var MessagePoller $adapter */
        $adapter = ComponentTestBuilder::create()
            ->withReference('someRef', $service)
            ->withPollingMetadata(PollingMetadata::create('test')->setHandledMessageLimit(1))
            ->build(InboundChannelAdapterBuilder::create(
                $inputChannelName,
                'someRef',
                InterfaceToCall::create($service::class, 'withoutReturnValue')
            )
                ->withEndpointId('test'));

        $adapter->receiveWithTimeout();

        $this->assertTrue($service->wasCalled());
    }


    /**
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_running_with_default_period_trigger()
    {
        $payload = 'testPayload';
        $inputChannelName = 'inputChannelName';
        $inputChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerStoppingService::create($payload);

        /** @var InterceptedConsumerRunner $inboundChannelAdapter */
        $inboundChannelConsumerRunner = ComponentTestBuilder::create()
            ->withChannel($inputChannelName, $inputChannel)
            ->withReference('someRef', $inboundChannelAdapterStoppingService)
            ->withPollingMetadata(PollingMetadata::create('test'))
            ->withRegisteredChannelAdapter(InboundChannelAdapterBuilder::create(
                $inputChannelName,
                'someRef',
                InterfaceToCall::create($inboundChannelAdapterStoppingService::class, 'execute')
            )
                ->withEndpointId('test'))
            ->getEndpointRunner('test')
        ;

        $consumer = $inboundChannelConsumerRunner->createConsumer(null);
        $inboundChannelAdapterStoppingService->setConsumerLifecycle($consumer);
        $consumer->run();

        $this->assertEquals(
            $payload,
            $inputChannel->receive()->getPayload()
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_running_with_message_consumption_limit()
    {
        $payload = 'testPayload';
        $requestChannelName = 'requestChannelName';
        $requestChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerContinuouslyWorkingService::createWithReturn($payload);

        $componentTest = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference('someRef', $inboundChannelAdapterStoppingService)
            ->withPollingMetadata(PollingMetadata::create('test')->setHandledMessageLimit(1))
            ->withRegisteredChannelAdapter(
                InboundChannelAdapterBuilder::create(
                    $requestChannelName,
                    'someRef',
                    InterfaceToCall::create($inboundChannelAdapterStoppingService::class, 'executeReturn')
                )
                ->withEndpointId('test')
            );

        $componentTest->runEndpoint('test');

        $this->assertEquals($payload, $requestChannel->receive()->getPayload());
        $this->assertNull($requestChannel->receive());
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_running_with_interceptor_from_interface_method_annotation()
    {
        $payload = 'testPayload';
        $requestChannelName = 'requestChannelName';
        $requestChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerContinuouslyWorkingService::createWithReturn($payload);

        $transactionOne = NullTransaction::start();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        /** @var EndpointRunner $inboundChannelAdapter */
        $inboundChannelConsumerRunner = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference('someRef', $inboundChannelAdapterStoppingService)
            ->withReference('transactionFactory2', $transactionFactoryOne)
            ->withPollingMetadata(PollingMetadata::create('test')->setHandledMessageLimit(1))
            ->withRegisteredChannelAdapter(
                InboundChannelAdapterBuilder::create(
                    $requestChannelName,
                    'someRef',
                    InterfaceToCall::create($inboundChannelAdapterStoppingService::class, 'executeReturnWithInterceptor')
                )
                ->withEndpointId('test')
                ->addAroundInterceptor(AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(InterfaceToCallRegistry::createEmpty(), new TransactionInterceptor(), 'transactional', 1, ''))
            )
            ->getEndpointRunner('test');

        $inboundChannelConsumerRunner->runEndpointWithExecutionPollingMetadata();

        $this->assertTrue($transactionOne->isCommitted());
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_running_with_interceptor_from_interface_class_annotation()
    {
        $payload = 'testPayload';
        $requestChannelName = 'requestChannelName';
        $requestChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerContinuouslyWorkingService::createWithReturn($payload);

        $transactionOne = NullTransaction::start();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        /** @var EndpointRunner $inboundChannelAdapter */
        $inboundChannelAdapter = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference('someRef', $inboundChannelAdapterStoppingService)
            ->withReference('transactionFactory1', $transactionFactoryOne)
            ->withPollingMetadata(PollingMetadata::create('test')->setHandledMessageLimit(1))
            ->withRegisteredChannelAdapter(
                InboundChannelAdapterBuilder::create(
                    $requestChannelName,
                    'someRef',
                    InterfaceToCall::create($inboundChannelAdapterStoppingService::class, 'executeReturn')
                )
                ->withEndpointId('test')
                ->addAroundInterceptor(AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(InterfaceToCallRegistry::createEmpty(), new TransactionInterceptor(), 'transactional', 1, ''))
            )
            ->getEndpointRunner('test');

        $inboundChannelAdapter->runEndpointWithExecutionPollingMetadata();

        $this->assertTrue($transactionOne->isCommitted());
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_running_with_interceptor_from_endpoint_annotation()
    {
        $payload = 'testPayload';
        $requestChannelName = 'requestChannelName';
        $requestChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerContinuouslyWorkingService::createWithReturn($payload);

        $transactionOne = NullTransaction::start();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        /** @var EndpointRunner $inboundChannelAdapter */
        $inboundChannelAdapter = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference('someRef', $inboundChannelAdapterStoppingService)
            ->withReference('transactionFactory0', $transactionFactoryOne)
            ->withPollingMetadata(PollingMetadata::create('test')->setHandledMessageLimit(1))
            ->withRegisteredChannelAdapter(
                InboundChannelAdapterBuilder::create(
                    $requestChannelName,
                    'someRef',
                    InterfaceToCall::create($inboundChannelAdapterStoppingService::class, 'executeReturnWithInterceptor')
                )
                ->withEndpointId('test')
                ->addAroundInterceptor(AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(InterfaceToCallRegistry::createEmpty(), new TransactionInterceptor(), 'transactional', 1, ''))
                ->withEndpointAnnotations([new AttributeDefinition(Transactional::class, [['transactionFactory0']])])
            )
            ->getEndpointRunner('test');

        $inboundChannelAdapter->runEndpointWithExecutionPollingMetadata();

        $this->assertTrue($transactionOne->isCommitted());
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_running_with_memory_limit()
    {
        $payload = 'testPayload';
        $requestChannelName = 'requestChannelName';
        $requestChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerContinuouslyWorkingService::createWithReturn($payload);

        /** @var EndpointRunner $inboundChannelAdapter */
        $inboundChannelAdapter = ComponentTestBuilder::create()
            ->withChannel($requestChannelName, $requestChannel)
            ->withReference('someRef', $inboundChannelAdapterStoppingService)
            ->withPollingMetadata(PollingMetadata::create('test')->setMemoryLimitInMegaBytes(1))
            ->withRegisteredChannelAdapter(
                InboundChannelAdapterBuilder::create(
                    $requestChannelName,
                    'someRef',
                    InterfaceToCall::create($inboundChannelAdapterStoppingService::class, 'executeReturn')
                )
                ->withEndpointId('test')
            )
            ->getEndpointRunner('test');

        $inboundChannelAdapter->runEndpointWithExecutionPollingMetadata();

        $this->assertNull($requestChannel->receive());
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_running_with_custom_trigger()
    {
        $payload = 'testPayload';
        $inputChannelName = 'inputChannelName';
        $inputChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerStoppingService::create($payload);

        /** @var InterceptedConsumerRunner $inboundChannelAdapter */
        $inboundChannelAdapter = ComponentTestBuilder::create()
            ->withChannel($inputChannelName, $inputChannel)
            ->withReference('someRef', $inboundChannelAdapterStoppingService)
            ->withPollingMetadata(
                PollingMetadata::create('test')
                    ->setFixedRateInMilliseconds(1)
                    ->setInitialDelayInMilliseconds(0)
            )
            ->withRegisteredChannelAdapter(
                InboundChannelAdapterBuilder::create(
                    $inputChannelName,
                    'someRef',
                    InterfaceToCall::create($inboundChannelAdapterStoppingService::class, 'execute')
                )
                ->withEndpointId('test')
            )
            ->getEndpointRunner('test');

        $consumer = $inboundChannelAdapter->createConsumer(null);
        $inboundChannelAdapterStoppingService->setConsumerLifecycle($consumer);
        $consumer->run();

        $this->assertEquals(
            $payload,
            $inputChannel->receive()->getPayload()
        );
    }

    public function test_acking_message_when_ack_available_in_message_header_in_inbound_channel_adapter()
    {
        // write for real implementation tests, if is acked correctly

        $acknowledgementCallback = NullAcknowledgementCallback::create();
        $message = MessageBuilder::withPayload('some')
            ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, 'amqpAcker')
            ->setHeader('amqpAcker', $acknowledgementCallback)
            ->build();
        $inputChannelName = 'inputChannelName';
        $inputChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerStoppingService::create($message);

        /** @var EndpointRunner $inboundChannelAdapter */
        $inboundChannelAdapter = ComponentTestBuilder::create()
            ->withChannel($inputChannelName, $inputChannel)
            ->withReference('someRef', $inboundChannelAdapterStoppingService)
            ->withPollingMetadata(
                PollingMetadata::create('test')
                    ->setFixedRateInMilliseconds(1)
                    ->setInitialDelayInMilliseconds(0)
                    ->setHandledMessageLimit(1)
                    ->setExecutionAmountLimit(1)
            )
            ->withRegisteredChannelAdapter(
                InboundChannelAdapterBuilder::create(
                    $inputChannelName,
                    'someRef',
                    InterfaceToCall::create($inboundChannelAdapterStoppingService::class, 'execute')
                )
                ->withEndpointId('test')
            )
            ->getEndpointRunner('test');

        $inboundChannelAdapter->runEndpointWithExecutionPollingMetadata();

        $this->assertTrue($acknowledgementCallback->isAcked());
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_throwing_exception_if_reference_service_has_no_passed_method()
    {
        $payload = 'testPayload';
        $inputChannelName = 'inputChannelName';
        $inputChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerStoppingService::create($payload);
        $componentTest = ComponentTestBuilder::create()
            ->withChannel($inputChannelName, $inputChannel)
            ->withReference('someRef', $inboundChannelAdapterStoppingService)
            ->withPollingMetadata(PollingMetadata::create('test'));

        $this->expectException(InvalidArgumentException::class);

        $componentTest->build(
            InboundChannelAdapterBuilder::create(
                $inputChannelName,
                'someRef',
                InterfaceToCall::create($inboundChannelAdapterStoppingService::class, 'notExistingMethod')
            )
        );
    }
}
