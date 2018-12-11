<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Endpoint;

use Test\SimplyCodedSoftware\Messaging\Fixture\Endpoint\ConsumerStoppingService;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceExpectingNoArguments;
use Test\SimplyCodedSoftware\Messaging\Fixture\Service\ServiceExpectingOneArgument;
use Test\SimplyCodedSoftware\Messaging\Fixture\Transaction\FakeTransactionFactory;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Scheduling\PeriodicTrigger;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
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
            ->withConsumerName("test")
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createWith([
                    "someRef" => ServiceExpectingOneArgument::create()
                ])
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
            ->withConsumerName("test")
            ->build(
                InMemoryChannelResolver::createEmpty(),
                InMemoryReferenceSearchService::createWith([
                    "someRef" => ServiceExpectingNoArguments::create()
                ])
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
            ->withConsumerName("test")
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $inputChannelName => $inputChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    "someRef" => $inboundChannelAdapterStoppingService
            ])
        );

        $inboundChannelAdapterStoppingService->setConsumerLifecycle($inboundChannel);
        $inboundChannel->start();

        $this->assertEquals(
            $payload,
            $inputChannel->receive()->getPayload()
        );
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
            ->withConsumerName("test")
            ->withTrigger(PeriodicTrigger::create(1, 0))
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $inputChannelName => $inputChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    "someRef" => $inboundChannelAdapterStoppingService
                ])
            );

        $inboundChannelAdapterStoppingService->setConsumerLifecycle($inboundChannel);
        $inboundChannel->start();

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
            ->withConsumerName("test")
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $inputChannelName => $inputChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    "someRef" => $inboundChannelAdapterStoppingService
                ])
            );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_calling_with_transactions_enabled()
    {
        $payload = "testPayload";
        $inputChannelName = "inputChannelName";
        $inputChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerStoppingService::create($payload);
        $fakeTransaction = FakeTransactionFactory::create();

        $inboundChannel = InboundChannelAdapterBuilder::create(
            $inputChannelName, "someRef", "execute"
        )
            ->withConsumerName("test")
            ->withTransactionFactories(["tx"])
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $inputChannelName => $inputChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    "someRef" => $inboundChannelAdapterStoppingService,
                    "tx" => $fakeTransaction
                ])
            );

        $inboundChannelAdapterStoppingService->setConsumerLifecycle($inboundChannel);

        $this->assertNull($fakeTransaction->getCurrentTransaction());
        $inboundChannel->start();
        $this->assertTrue($fakeTransaction->getCurrentTransaction()->isCommitted(), true);
    }
}