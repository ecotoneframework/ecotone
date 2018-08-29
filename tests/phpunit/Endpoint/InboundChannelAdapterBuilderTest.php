<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Endpoint;

use Fixture\Endpoint\ConsumerStoppingService;
use Fixture\Service\ServiceExpectingNoArguments;
use Fixture\Service\ServiceExpectingOneArgument;
use Fixture\Transaction\FakeTransactionFactory;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Scheduling\PeriodicTrigger;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class InboundChannelAdapterBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InboundChannelAdapterBuilderTest extends MessagingTest
{
    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_throwing_exception_if_passed_reference_service_has_parameters()
    {
        $this->expectException(InvalidArgumentException::class);

        InboundChannelAdapterBuilder::create(
            "channelName", "someRef", "withReturnValue"
        )->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                "someRef" => ServiceExpectingOneArgument::create()
            ])
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_passed_reference_should_return_parameters()
    {
        $this->expectException(InvalidArgumentException::class);

        InboundChannelAdapterBuilder::create(
            "channelName", "someRef", "withoutReturnValue"
        )->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                "someRef" => ServiceExpectingNoArguments::create()
            ])
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
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