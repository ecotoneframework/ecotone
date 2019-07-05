<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Endpoint;

use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Scheduling\PeriodicTrigger;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\Messaging\Transaction\Null\NullTransaction;
use SimplyCodedSoftware\Messaging\Transaction\Null\NullTransactionFactory;
use SimplyCodedSoftware\Messaging\Transaction\Transactional;
use SimplyCodedSoftware\Messaging\Transaction\TransactionInterceptor;
use Test\SimplyCodedSoftware\Messaging\Fixture\Endpoint\ConsumerContinuouslyWorkingService;
use Test\SimplyCodedSoftware\Messaging\Fixture\Endpoint\ConsumerStoppingService;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceExpectingNoArguments;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceExpectingOneArgument;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;

/**
 * Class InboundChannelAdapterBuilderTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InboundChannelAdapterBuilderTest extends MessagingTest
{
    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_passed_reference_service_has_parameters()
    {
        $this->expectException(InvalidArgumentException::class);

        InboundChannelAdapterBuilder::create(
            "channelName", "someRef", "withReturnValue"
        )
            ->withEndpointId("test")
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createWith([
                    "someRef" => ServiceExpectingOneArgument::create()
                ]),
                PollingMetadata::create("test")
            );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_passed_reference_should_return_parameters()
    {
        $this->expectException(InvalidArgumentException::class);

        InboundChannelAdapterBuilder::create(
            "channelName", "someRef", "withoutReturnValue"
        )
            ->withEndpointId("test")
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createWith([
                    "someRef" => ServiceExpectingNoArguments::create()
                ]),
                PollingMetadata::create("test")
            );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_running_with_default_period_trigger()
    {
        $payload = "testPayload";
        $inputChannelName = "inputChannelName";
        $inputChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerStoppingService::create($payload);

        $inboundChannel = InboundChannelAdapterBuilder::create(
            $inputChannelName, "someRef", "execute"
        )
            ->withEndpointId("test")
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $inputChannelName => $inputChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    "someRef" => $inboundChannelAdapterStoppingService
                ]),
                PollingMetadata::create("test")
            );

        $inboundChannelAdapterStoppingService->setConsumerLifecycle($inboundChannel);
        $inboundChannel->run();

        $this->assertEquals(
            $payload,
            $inputChannel->receive()->getPayload()
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_running_with_message_consumption_limit()
    {
        $payload = "testPayload";
        $requestChannelName = "requestChannelName";
        $requestChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerContinuouslyWorkingService::createWithReturn($payload);

        $inboundChannel = InboundChannelAdapterBuilder::create(
            $requestChannelName, "someRef", "executeReturn"
        )
            ->withEndpointId("test")
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $requestChannelName => $requestChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    "someRef" => $inboundChannelAdapterStoppingService
                ]),
                PollingMetadata::create("test")
                    ->setHandledMessageLimit(1)
            );

        $inboundChannel->run();

        $this->assertEquals($payload, $requestChannel->receive()->getPayload());
        $this->assertNull($requestChannel->receive());
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_running_with_interceptor_from_interface_method_annotation()
    {
        $payload = "testPayload";
        $requestChannelName = "requestChannelName";
        $requestChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerContinuouslyWorkingService::createWithReturn($payload);

        $transactionOne = NullTransaction::start();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        $inboundChannel = InboundChannelAdapterBuilder::create(
            $requestChannelName, "someRef", "executeReturnWithInterceptor"
        )
            ->withEndpointId("test")
            ->addAroundInterceptor(AroundInterceptorReference::createWithDirectObject("transactionInterceptor",new TransactionInterceptor(), "transactional", 1, ""))
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $requestChannelName => $requestChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    "someRef" => $inboundChannelAdapterStoppingService,
                    "transactionFactory2" => $transactionFactoryOne
                ]),
                PollingMetadata::create("test")
                    ->setHandledMessageLimit(1)
            );

        $inboundChannel->run();

        $this->assertTrue($transactionOne->isCommitted());
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_running_with_interceptor_from_interface_class_annotation()
    {
        $payload = "testPayload";
        $requestChannelName = "requestChannelName";
        $requestChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerContinuouslyWorkingService::createWithReturn($payload);

        $transactionOne = NullTransaction::start();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        $inboundChannel = InboundChannelAdapterBuilder::create(
            $requestChannelName, "someRef", "executeReturn"
        )
            ->withEndpointId("test")
            ->addAroundInterceptor(AroundInterceptorReference::createWithDirectObject("transactionInterceptor",new TransactionInterceptor(), "transactional", 1, ""))
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $requestChannelName => $requestChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    "someRef" => $inboundChannelAdapterStoppingService,
                    "transactionFactory1" => $transactionFactoryOne
                ]),
                PollingMetadata::create("test")
                    ->setHandledMessageLimit(1)
            );

        $inboundChannel->run();

        $this->assertTrue($transactionOne->isCommitted());
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_running_with_interceptor_from_endpoint_annotation()
    {
        $payload = "testPayload";
        $requestChannelName = "requestChannelName";
        $requestChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerContinuouslyWorkingService::createWithReturn($payload);

        $transactionOne = NullTransaction::start();
        $transactionFactoryOne = NullTransactionFactory::createWithPredefinedTransaction($transactionOne);

        $inboundChannel = InboundChannelAdapterBuilder::create(
            $requestChannelName, "someRef", "executeReturnWithInterceptor"
        )
            ->withEndpointId("test")
            ->addAroundInterceptor(AroundInterceptorReference::createWithDirectObject("transactionInterceptor",new TransactionInterceptor(), "transactional", 1, ""))
            ->withEndpointAnnotations([Transactional::createWith(["transactionFactory0"])])
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $requestChannelName => $requestChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    "someRef" => $inboundChannelAdapterStoppingService,
                    "transactionFactory0" => $transactionFactoryOne
                ]),
                PollingMetadata::create("test")
                    ->setHandledMessageLimit(1)
            );

        $inboundChannel->run();

        $this->assertTrue($transactionOne->isCommitted());
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_running_with_memory_limit()
    {
        $payload = "testPayload";
        $requestChannelName = "requestChannelName";
        $requestChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerContinuouslyWorkingService::createWithReturn($payload);

        $inboundChannel = InboundChannelAdapterBuilder::create(
            $requestChannelName, "someRef", "executeReturn"
        )
            ->withEndpointId("test")
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $requestChannelName => $requestChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    "someRef" => $inboundChannelAdapterStoppingService
                ]),
                PollingMetadata::create("test")
                    ->setMemoryLimitInMegaBytes(1)
            );

        $inboundChannel->run();

        $this->assertNull($requestChannel->receive());
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_running_with_custom_trigger()
    {
        $payload = "testPayload";
        $inputChannelName = "inputChannelName";
        $inputChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerStoppingService::create($payload);

        $inboundChannel = InboundChannelAdapterBuilder::create(
            $inputChannelName, "someRef", "execute"
        )
            ->withEndpointId("test")
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $inputChannelName => $inputChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    "someRef" => $inboundChannelAdapterStoppingService
                ]),
                PollingMetadata::create("test")
                    ->setFixedRateInMilliseconds(1)
                    ->setInitialDelayInMilliseconds(0)
            );

        $inboundChannelAdapterStoppingService->setConsumerLifecycle($inboundChannel);
        $inboundChannel->run();

        $this->assertEquals(
            $payload,
            $inputChannel->receive()->getPayload()
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_reference_service_has_no_passed_method()
    {
        $payload = "testPayload";
        $inputChannelName = "inputChannelName";
        $inputChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerStoppingService::create($payload);

        $this->expectException(InvalidArgumentException::class);

        InboundChannelAdapterBuilder::create(
            $inputChannelName, "someRef", "notExistingMethod"
        )
            ->withEndpointId("test")
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $inputChannelName => $inputChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    "someRef" => $inboundChannelAdapterStoppingService
                ]),
                PollingMetadata::create("test")
            );
    }
}