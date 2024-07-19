<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Endpoint\Poller;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Lite\Test\FlowTestSupport;
use Ecotone\Messaging\Channel\ExceptionalQueueChannel;
use Ecotone\Messaging\Channel\PollableChannel\InMemory\InMemoryAcknowledgeCallback;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Endpoint\NullAcknowledgementCallback;
use Ecotone\Messaging\Endpoint\PollingConsumer\RejectMessageException;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\ComponentTestBuilder;
use InvalidArgumentException;
use RuntimeException;
use Test\Ecotone\Messaging\Fixture\Endpoint\ConsumerStoppingService;
use Test\Ecotone\Messaging\Fixture\Endpoint\ConsumerThrowingExceptionService;
use Test\Ecotone\Messaging\Fixture\Handler\DataReturningService;
use Test\Ecotone\Messaging\Fixture\Handler\SuccessServiceActivator;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class PollingConsumerBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Endpoint
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class PollingConsumerBuilderTest extends MessagingTest
{
    /**
     * @throws MessagingException
     */
    public function test_creating_consumer_with_default_period_trigger()
    {
        $inputChannelName = 'inputChannelName';
        $inputChannel = QueueChannel::create();

        $directObjectReference = ConsumerStoppingService::create(null);
        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::create($inputChannelName, $inputChannel))
            ->withPollingMetadata(PollingMetadata::create('test')->withTestingSetup())
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference($directObjectReference, 'executeNoReturn')
                    ->withEndpointId('test')
                    ->withInputChannelName($inputChannelName)
            )
            ->build()
        ;

        $inputChannel->send(MessageBuilder::withPayload('somePayload')->build());
        $messaging->run('test');

        $this->assertEquals(
            'somePayload',
            $directObjectReference->getReceivedPayload()
        );
    }

    /**
     * @throws MessagingException
     */
    public function test_passing_message_to_error_channel_on_failure()
    {
        $inputChannelName = 'inputChannelName';
        $errorChannelName = 'errorChannelName';
        $inputChannel = QueueChannel::create();
        $errorChannel = QueueChannel::create();

        $directObjectReference = ConsumerThrowingExceptionService::create();

        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::create($inputChannelName, $inputChannel))
            ->withChannel(SimpleMessageChannelBuilder::create($errorChannelName, $errorChannel))
            ->withPollingMetadata(
                PollingMetadata::create('test')
                    ->withTestingSetup(failAtError: false)
                    ->setErrorChannelName($errorChannelName)
            )
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference($directObjectReference, 'execute')
                    ->withEndpointId('test')
                    ->withInputChannelName($inputChannelName)
            )
            ->build()
        ;

        $messaging->sendDirectToChannel($inputChannelName, 'somePayload');
        $messaging->run('test');

        $this->assertNotNull($errorChannel->receive());
    }

    public function test_retrying_template_should_not_handle_exception_thrown_during_handling_of_message()
    {
        $inputChannelName = 'inputChannelName';
        $inputChannel = QueueChannel::create();

        $directObjectReference = ConsumerThrowingExceptionService::create();
        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::create($inputChannelName, $inputChannel))
            ->withPollingMetadata(
                PollingMetadata::create('test')
                    ->withTestingSetup(failAtError: true)
                    ->setConnectionRetryTemplate(RetryTemplateBuilder::fixedBackOff(1)->maxRetryAttempts(1))
            )
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference($directObjectReference, 'execute')
                    ->withEndpointId('test')
                    ->withInputChannelName($inputChannelName)
            )
            ->build();

        $messaging->sendDirectToChannel($inputChannelName, 'somePayload');
        $exceptionThrown = false;

        try {
            $messaging->run('test');
        } catch (RuntimeException $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
        $this->assertEquals(1, $directObjectReference->getCalled());
    }

    public function test_retrying_template_should_handle_exceptions_thrown_before_handling_of_message()
    {
        $inputChannelName = 'inputChannelName';
        $inputChannel = ExceptionalQueueChannel::createWithExceptionOnReceive();

        $directObjectReference = ConsumerThrowingExceptionService::create();
        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::create($inputChannelName, $inputChannel))
            ->withPollingMetadata(
                PollingMetadata::create('test')
                    ->withTestingSetup(failAtError: false)
                    ->setConnectionRetryTemplate(RetryTemplateBuilder::fixedBackOff(1)->maxRetryAttempts(2))
            )
            ->withMessageHandler(
                ServiceActivatorBuilder::createWithDirectReference($directObjectReference, 'execute')
                    ->withEndpointId('test')
                    ->withInputChannelName($inputChannelName)
            )
            ->build();

        $messaging->sendDirectToChannel($inputChannelName, 'somePayload');

        try {
            $messaging->run('test');
        } catch (RuntimeException $e) {
        }

        $this->assertEquals(3, $inputChannel->getExceptionCount());
    }

    public function test_acking_message_when_ack_available_in_message_header()
    {
        $acknowledgementCallback = NullAcknowledgementCallback::create();
        $message = MessageBuilder::withPayload('some')
            ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, 'amqpAcker')
            ->setHeader('amqpAcker', $acknowledgementCallback)
            ->build();
        $inputChannelName = 'inputChannel';
        $inputChannel = QueueChannel::create();
        $messageHandler = DataReturningService::createServiceActivatorBuilder('some')
                            ->withEndpointId('some-id')
                            ->withInputChannelName($inputChannelName);

        $messaging = $this->createPollingConsumer($inputChannelName, $inputChannel, $messageHandler);

        $messaging->sendMessageDirectToChannel($inputChannelName, $message);

        $messaging->run('some-id');

        $this->assertTrue($acknowledgementCallback->isAcked());
    }

    public function test_requeing_message_on_gateway_failure()
    {
        $acknowledgementCallback = NullAcknowledgementCallback::create();
        $message = MessageBuilder::withPayload('some')
            ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, 'amqpAcker')
            ->setHeader('amqpAcker', $acknowledgementCallback)
            ->build();

        $inputChannelName = 'inputChannel';
        $inputChannel = QueueChannel::create();
        $messageHandler = DataReturningService::createExceptionalServiceActivatorBuilder()
            ->withEndpointId('some-id')
            ->withInputChannelName($inputChannelName);

        $messaging = $this->createPollingConsumer($inputChannelName, $inputChannel, $messageHandler);

        $messaging->sendMessageDirectToChannel($inputChannelName, $message);
        $messaging->run('some-id');

        $this->assertTrue($acknowledgementCallback->isRequeued());
    }

    public function test_throwing_exception_and_requeuing_when_stop_on_error_defined()
    {
        $acknowledgementCallback = NullAcknowledgementCallback::create();
        $message = MessageBuilder::withPayload('some')
            ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, 'amqpAcker')
            ->setHeader('amqpAcker', $acknowledgementCallback)
            ->build();

        $inputChannelName = 'inputChannel';
        $inputChannel = QueueChannel::create();
        $messageHandler = DataReturningService::createExceptionalServiceActivatorBuilder()
            ->withEndpointId('some-id')
            ->withInputChannelName($inputChannelName);

        $messaging = $this->createPollingConsumer($inputChannelName, $inputChannel, $messageHandler, true);

        $this->expectException(InvalidArgumentException::class);

        $messaging->sendMessageDirectToChannel($inputChannelName, $message);
        $messaging->run('some-id');

        $this->assertTrue($acknowledgementCallback->isRequeued());
    }

    public function test_throwing_exception_and_rejecting_message()
    {
        $acknowledgementCallback = NullAcknowledgementCallback::create();
        $message = MessageBuilder::withPayload('some')
            ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, 'amqpAcker')
            ->setHeader('amqpAcker', $acknowledgementCallback)
            ->build();

        $inputChannelName = 'inputChannel';
        $inputChannel = QueueChannel::create();
        $messageHandler = DataReturningService::createServiceActivatorBuilderWithRejectException()
            ->withEndpointId('some-id')
            ->withInputChannelName($inputChannelName);

        $messaging = $this->createPollingConsumer($inputChannelName, $inputChannel, $messageHandler, true);

        $this->expectException(RejectMessageException::class);

        $messaging->sendMessageDirectToChannel($inputChannelName, $message);
        $messaging->run('some-id');

        $this->assertTrue($acknowledgementCallback->isRejected());
        $this->assertFalse($acknowledgementCallback->isRequeued());
    }

    public function test_acking_message_with_fully_running_ecotone()
    {
        $messageChannelName = 'async_channel';
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [SuccessServiceActivator::class],
            [new SuccessServiceActivator()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel($messageChannelName),
            ]
        );

        $ecotoneTestSupport->sendDirectToChannel('handle_channel');
        $ecotoneTestSupport->run($messageChannelName, ExecutionPollingMetadata::createWithDefaults()->withTestingSetup());

        $headers = $ecotoneTestSupport->sendQueryWithRouting('get_last_message_headers');

        /** @var InMemoryAcknowledgeCallback $acknowledge */
        $acknowledge = $headers[$headers[MessageHeaders::CONSUMER_ACK_HEADER_LOCATION]];
        $this->assertInstanceOf(InMemoryAcknowledgeCallback::class, $acknowledge);
        $this->assertNull($ecotoneTestSupport->getMessageChannel($messageChannelName)->receive());
    }

    public function test_acking_on_gateway_failure_when_error_channel_defined()
    {
        $acknowledgementCallback = NullAcknowledgementCallback::create();
        $message = MessageBuilder::withPayload('some')
            ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, 'amqpAcker')
            ->setHeader('amqpAcker', $acknowledgementCallback)
            ->build();

        $inputChannelName = 'inputChannel';
        $errorChannelName = 'errorChannelName';
        $inputChannel = QueueChannel::create();
        $errorChannel = QueueChannel::create();
        $messageHandler = DataReturningService::createExceptionalServiceActivatorBuilder()
            ->withEndpointId('some-id')
            ->withInputChannelName($inputChannelName);

        $messaging = $this->createPollingConsumerWithCustomConfiguration(
            [
                $inputChannelName => $inputChannel,
                $errorChannelName => $errorChannel,
            ],
            $messageHandler,
            PollingMetadata::create('some-id')
                ->setExecutionAmountLimit(1)
                ->setErrorChannelName($errorChannelName)
        );

        $messaging->sendMessageDirectToChannel($inputChannelName, $message);
        $messaging->run('some-id');

        $this->assertNotNull($errorChannel);
        $this->assertTrue($acknowledgementCallback->isAcked());
    }

    public function test_throwing_exception_and_requeing_when_stop_on_failure_defined_and_error_channel()
    {
        $acknowledgementCallback = NullAcknowledgementCallback::create();
        $message = MessageBuilder::withPayload('some')
            ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, 'amqpAcker')
            ->setHeader('amqpAcker', $acknowledgementCallback)
            ->build();

        $inputChannelName = 'inputChannel';
        $errorChannelName = 'errorChannelName';
        $inputChannel = QueueChannel::create();
        $errorChannel = QueueChannel::create();
        $messageHandler = DataReturningService::createExceptionalServiceActivatorBuilder()
            ->withEndpointId('some-id')
            ->withInputChannelName($inputChannelName);

        $messaging = $this->createPollingConsumerWithCustomConfiguration(
            [
                $inputChannelName => $inputChannel,
                $errorChannelName => $errorChannel,
            ],
            $messageHandler,
            PollingMetadata::create('some-id')
                ->setStopOnError(true)
                ->setExecutionAmountLimit(1)
                ->setErrorChannelName($errorChannelName)
        );

        $this->expectException(InvalidArgumentException::class);

        $messaging->sendMessageDirectToChannel($inputChannelName, $message);
        $messaging->run('some-id');

        $this->assertTrue($acknowledgementCallback->isRequeued());
        $this->assertNull($errorChannel->receive());
    }

    public function test_finish_when_no_messages(): void
    {
        $inputChannelName = 'async_channel';
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [SuccessServiceActivator::class],
            [new SuccessServiceActivator()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel($inputChannelName),
            ]
        );

        $ecotoneTestSupport->sendDirectToChannel('handle_channel');

        $ecotoneTestSupport->run($inputChannelName, ExecutionPollingMetadata::createWithFinishWhenNoMessages());

        $this->assertSame(
            1,
            $ecotoneTestSupport->sendQueryWithRouting('get_number_of_calls')
        );
        $this->assertNull(
            $ecotoneTestSupport->getMessageChannel($inputChannelName)->receive()
        );
    }

    public function test_finish_when_no_messages_with_more_messages(): void
    {
        $inputChannelName = 'async_channel';
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [SuccessServiceActivator::class],
            [new SuccessServiceActivator()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel($inputChannelName),
            ]
        );

        $ecotoneTestSupport->sendDirectToChannel('handle_channel');
        $ecotoneTestSupport->sendDirectToChannel('handle_channel');
        $ecotoneTestSupport->sendDirectToChannel('handle_channel');

        $ecotoneTestSupport->run($inputChannelName, ExecutionPollingMetadata::createWithFinishWhenNoMessages());

        $this->assertSame(
            3,
            $ecotoneTestSupport->sendQueryWithRouting('get_number_of_calls')
        );
        $this->assertNull(
            $ecotoneTestSupport->getMessageChannel($inputChannelName)->receive()
        );
    }

    private function createPollingConsumer(
        string $inputChannelName,
        QueueChannel $inputChannel,
        MessageHandlerBuilder $messageHandler,
        bool $stopOnFailure = false
    ): FlowTestSupport {
        return ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::create($inputChannelName, $inputChannel))
            ->withPollingMetadata(
                PollingMetadata::create($messageHandler->getEndpointId())
                    ->withTestingSetup(failAtError: $stopOnFailure)
            )
            ->withMessageHandler(
                $messageHandler
                    ->withInputChannelName($inputChannelName)
            )
            ->build();
    }

    private function createPollingConsumerWithCustomConfiguration(array $channels, MessageHandlerBuilder $messageHandler, PollingMetadata  $pollingMetadata): FlowTestSupport
    {
        $componentTest = ComponentTestBuilder::create()
            ->withPollingMetadata($pollingMetadata);
        foreach ($channels as $channelName => $channel) {
            $componentTest = $componentTest->withChannel(SimpleMessageChannelBuilder::create($channelName, $channel));
        }

        return $componentTest
            ->withMessageHandler($messageHandler)
            ->build();
    }
}
