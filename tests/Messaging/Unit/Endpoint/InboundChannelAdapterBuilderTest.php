<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Endpoint;

use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Scheduling\PeriodicTrigger;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
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
    public function __test_throwing_exception_if_passed_reference_service_has_parameters()
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
                null
            );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function __test_passed_reference_should_return_parameters()
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
                null
            );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function __test_running_with_default_period_trigger()
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
                null
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
    public function __test_running_with_custom_trigger()
    {
        $payload = "testPayload";
        $inputChannelName = "inputChannelName";
        $inputChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerStoppingService::create($payload);

        $inboundChannel = InboundChannelAdapterBuilder::create(
            $inputChannelName, "someRef", "execute"
        )
            ->withEndpointId("test")
            ->withTrigger(PeriodicTrigger::create(1, 0))
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $inputChannelName => $inputChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    "someRef" => $inboundChannelAdapterStoppingService
                ]),
                null
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
    public function __test_throwing_exception_if_reference_service_has_no_passed_method()
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
                null
            );
    }
}