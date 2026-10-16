<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Endpoint;

use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use Ecotone\Messaging\Endpoint\NullAcknowledgementCallback;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Test\ComponentTestBuilder;
use Ecotone\Test\StaticMessageBuilder;
use Test\Ecotone\Messaging\Fixture\Endpoint\ConsumerContinuouslyWorkingService;
use Test\Ecotone\Messaging\Fixture\Endpoint\ConsumerStoppingService;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor\RecordOutcomeIn;
use Test\Ecotone\Messaging\Unit\MessagingTestCase;
use Throwable;

/**
 * Class InboundChannelAdapterBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Endpoint
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class InboundChannelAdapterBuilderTest extends MessagingTestCase
{
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

        $recorder = $this->outcomeRecorder();

        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::create($requestChannelName, $requestChannel))
            ->withReference('someRef', $inboundChannelAdapterStoppingService)
            ->withReference('methodRecorder', $recorder)
            ->withPollingMetadata(PollingMetadata::create('test')->withHandledMessageLimit(1))
            ->withInboundChannelAdapter(
                InboundChannelAdapterBuilder::create(
                    $requestChannelName,
                    'someRef',
                    InterfaceToCall::create($inboundChannelAdapterStoppingService::class, 'executeReturnWithInterceptor')
                )
                ->withEndpointId('test')
            )
            ->withAroundInterceptor(AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(InterfaceToCallRegistry::createEmpty(), $this->outcomeRecordingInterceptor(), 'record', 1, $inboundChannelAdapterStoppingService::class))
            ->build();

        $messaging->run('test');

        $this->assertSame('completed', $recorder->outcome);
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

        $recorder = $this->outcomeRecorder();

        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::create($requestChannelName, $requestChannel))
            ->withReference('someRef', $inboundChannelAdapterStoppingService)
            ->withReference('classRecorder', $recorder)
            ->withPollingMetadata(PollingMetadata::create('test')->withHandledMessageLimit(1))
            ->withInboundChannelAdapter(
                InboundChannelAdapterBuilder::create(
                    $requestChannelName,
                    'someRef',
                    InterfaceToCall::create($inboundChannelAdapterStoppingService::class, 'executeReturn')
                )
                    ->withEndpointId('test')
            )
            ->withAroundInterceptor(AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(InterfaceToCallRegistry::createEmpty(), $this->outcomeRecordingInterceptor(), 'record', 1, $inboundChannelAdapterStoppingService::class))
            ->build();

        $messaging->run('test');

        $this->assertSame('completed', $recorder->outcome);
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

        $recorder = $this->outcomeRecorder();

        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::create($requestChannelName, $requestChannel))
            ->withReference('someRef', $inboundChannelAdapterStoppingService)
            ->withReference('endpointRecorder', $recorder)
            ->withPollingMetadata(PollingMetadata::create('test')->withHandledMessageLimit(1))
            ->withInboundChannelAdapter(
                InboundChannelAdapterBuilder::create(
                    $requestChannelName,
                    'someRef',
                    InterfaceToCall::create($inboundChannelAdapterStoppingService::class, 'executeReturnWithInterceptor')
                )
                ->withEndpointId('test')
                ->withEndpointAnnotations([new AttributeDefinition(RecordOutcomeIn::class, ['endpointRecorder'])])
            )
            ->withAroundInterceptor(AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters(InterfaceToCallRegistry::createEmpty(), $this->outcomeRecordingInterceptor(), 'record', 1, $inboundChannelAdapterStoppingService::class))
            ->build();

        $messaging->run('test');

        $this->assertSame('completed', $recorder->outcome);
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

        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::create($requestChannelName, $requestChannel))
            ->withReference('someRef', $inboundChannelAdapterStoppingService)
            ->withPollingMetadata(PollingMetadata::create('test')->withMemoryLimitInMegaBytes(1))
            ->withInboundChannelAdapter(
                InboundChannelAdapterBuilder::create(
                    $requestChannelName,
                    'someRef',
                    InterfaceToCall::create($inboundChannelAdapterStoppingService::class, 'executeReturn')
                )
                ->withEndpointId('test')
            )
            ->build();

        $messaging->run('test');

        $this->assertNull($requestChannel->receive());
    }

    public function test_acking_message_when_ack_available_in_message_header_in_inbound_channel_adapter()
    {
        // write for real implementation tests, if is acked correctly

        $acknowledgementCallback = NullAcknowledgementCallback::create();
        $message = StaticMessageBuilder::withPayload('some')
            ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, 'amqpAcker')
            ->setHeader('amqpAcker', $acknowledgementCallback)
            ->build();
        $inputChannelName = 'inputChannelName';
        $inputChannel = QueueChannel::create();
        $inboundChannelAdapterStoppingService = ConsumerStoppingService::create($message);

        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::create($inputChannelName, $inputChannel))
            ->withReference('someRef', $inboundChannelAdapterStoppingService)
            ->withPollingMetadata(
                PollingMetadata::create('test')
                    ->withFixedRateInMilliseconds(1)
                    ->withInitialDelayInMilliseconds(0)
                    ->withHandledMessageLimit(1)
                    ->withExecutionAmountLimit(1)
            )
            ->withInboundChannelAdapter(
                InboundChannelAdapterBuilder::create(
                    $inputChannelName,
                    'someRef',
                    InterfaceToCall::create($inboundChannelAdapterStoppingService::class, 'execute')
                )
                ->withEndpointId('test')
            )
            ->build();

        $messaging->run('test');

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

        $this->expectException(InvalidArgumentException::class);

        ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::create($inputChannelName, $inputChannel))
            ->withReference('someRef', $inboundChannelAdapterStoppingService)
            ->withPollingMetadata(PollingMetadata::create('test'))
            ->withInboundChannelAdapter(
                InboundChannelAdapterBuilder::create(
                    $inputChannelName,
                    'someRef',
                    InterfaceToCall::create($inboundChannelAdapterStoppingService::class, 'notExistingMethod')
                )
            )
            ->build();
    }

    private function outcomeRecorder(): object
    {
        return new class () {
            public ?string $outcome = null;
        };
    }

    private function outcomeRecordingInterceptor(): object
    {
        return new class () {
            public function record(MethodInvocation $methodInvocation, ReferenceSearchService $referenceSearchService, RecordOutcomeIn $recordOutcomeIn): mixed
            {
                $recorder = $referenceSearchService->get($recordOutcomeIn->recorderReferenceName);
                try {
                    $result = $methodInvocation->proceed();
                } catch (Throwable $throwable) {
                    $recorder->outcome = 'failed';

                    throw $throwable;
                }
                $recorder->outcome = 'completed';

                return $result;
            }
        };
    }
}
