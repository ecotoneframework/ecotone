<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Endpoint\Poller;

use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Messaging\ErrorContext;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Channel\ExceptionalQueueChannel;
use Ecotone\Messaging\Channel\PollableChannel\InMemory\InMemoryAcknowledgeCallback;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\BootstrapConfiguration;
use Ecotone\Messaging\Endpoint\NullAcknowledgementCallback;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Test\ChannelTestSupport;
use Ecotone\Test\ComponentTestBuilder;
use Ecotone\Test\StaticMessageBuilder;
use Ecotone\Test\StubLogger;
use InvalidArgumentException;
use Test\Ecotone\Messaging\Fixture\Handler\DataReturningService;
use Test\Ecotone\Messaging\Fixture\Handler\FailureHandler\ExampleFailureCommandHandler;
use Test\Ecotone\Messaging\Fixture\Handler\FailureHandler\FailureErrorHandler;
use Test\Ecotone\Messaging\Fixture\Handler\SuccessServiceActivator;
use Test\Ecotone\Messaging\Unit\MessagingTestCase;

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
class PollingConsumerBuilderTest extends MessagingTestCase
{
    public function test_acking_message_when_ack_available_in_message_header()
    {
        $acknowledgementCallback = NullAcknowledgementCallback::create();
        $message = StaticMessageBuilder::withPayload('some')
            ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, 'amqpAcker')
            ->setHeader('amqpAcker', $acknowledgementCallback)
            ->build();
        $inputChannelName = 'inputChannel';
        $inputChannel = QueueChannel::create();
        $messageHandler = DataReturningService::createServiceActivatorBuilder('some')
                            ->withEndpointId('some-id')
                            ->withInputChannelName($inputChannelName);

        $messaging = $this->createPollingConsumer($inputChannelName, $inputChannel, $messageHandler);

        ChannelTestSupport::of($messaging)->sendMessageDirectToChannel($inputChannelName, $message);

        $messaging->run('some-id');

        $this->assertTrue($acknowledgementCallback->isAcked());
    }

    public function test_requeing_message_on_gateway_failure()
    {
        $acknowledgementCallback = NullAcknowledgementCallback::create();
        $message = StaticMessageBuilder::withPayload('some')
            ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, 'amqpAcker')
            ->setHeader('amqpAcker', $acknowledgementCallback)
            ->build();

        $inputChannelName = 'inputChannel';
        $inputChannel = QueueChannel::create();
        $messageHandler = DataReturningService::createExceptionalServiceActivatorBuilder()
            ->withEndpointId('some-id')
            ->withInputChannelName($inputChannelName);

        $messaging = $this->createPollingConsumer($inputChannelName, $inputChannel, $messageHandler);

        ChannelTestSupport::of($messaging)->sendMessageDirectToChannel($inputChannelName, $message);
        $messaging->run('some-id');

        $this->assertTrue($acknowledgementCallback->isRequeued());
    }

    public function test_throwing_exception_and_requeuing_when_stop_on_error_defined()
    {
        $acknowledgementCallback = NullAcknowledgementCallback::create();
        $message = StaticMessageBuilder::withPayload('some')
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

        ChannelTestSupport::of($messaging)->sendMessageDirectToChannel($inputChannelName, $message);
        $messaging->run('some-id');

        $this->assertTrue($acknowledgementCallback->isRequeued());
    }

    public function test_throwing_exception_and_rejecting_message()
    {
        $acknowledgementCallback = NullAcknowledgementCallback::create();
        $message = StaticMessageBuilder::withPayload('some')
            ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, 'amqpAcker')
            ->setHeader('amqpAcker', $acknowledgementCallback)
            ->build();

        $inputChannelName = 'inputChannel';
        $inputChannel = QueueChannel::create();
        $messageHandler = DataReturningService::createServiceActivatorBuilderWithRejectException()
            ->withEndpointId('some-id')
            ->withInputChannelName($inputChannelName);

        $messaging = $this->createPollingConsumer($inputChannelName, $inputChannel, $messageHandler, true);

        ChannelTestSupport::of($messaging)->sendMessageDirectToChannel($inputChannelName, $message);
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
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel($messageChannelName)],
            enabledModules: [],
        );

        $ecotoneTestSupport->sendDirectToChannel('handle_channel');
        $ecotoneTestSupport->run($messageChannelName, ExecutionPollingMetadata::createWithDefaults()->withTestingSetup());

        $headers = $ecotoneTestSupport->sendQueryWithRouting('get_last_message_headers');

        /** @var InMemoryAcknowledgeCallback $acknowledge */
        $acknowledge = $headers[$headers[MessageHeaders::CONSUMER_ACK_HEADER_LOCATION]];
        $this->assertInstanceOf(InMemoryAcknowledgeCallback::class, $acknowledge);
        $this->assertNull(ChannelTestSupport::of($ecotoneTestSupport)->getMessageChannel($messageChannelName)->receive());
    }

    public function test_sending_to_error_channel()
    {
        $asyncChannelName = 'async';
        $errorChannelName = 'errorChannelName';

        $messaging = EcotoneLite::bootstrapFlowTesting(
            [ExampleFailureCommandHandler::class],
            [new ExampleFailureCommandHandler(), 'logger' => $logger = StubLogger::create()],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel($asyncChannelName),
                SimpleMessageChannelBuilder::createQueueChannel($errorChannelName),
                PollingMetadata::create('async')->withStopOnError(false)->withExecutionAmountLimit(1)->withErrorChannelName($errorChannelName),
            ],
            enabledModules: [],
        );

        $messaging->sendCommandWithRouting('handler.fail', ['command' => 0]);

        $messaging->run($asyncChannelName);

        $message = ChannelTestSupport::of($messaging)->getMessageChannel($errorChannelName)->receive();
        $this->assertNotNull($message);
        $this->assertTrue($message->getHeaders()->containsKey(ErrorContext::EXCEPTION_MESSAGE));
    }

    public function test_receiving_error_message_with_asynchronous_handler()
    {
        $asyncChannelName = 'async';

        $messaging = EcotoneLite::bootstrapFlowTesting(
            [ExampleFailureCommandHandler::class, FailureErrorHandler::class],
            [new ExampleFailureCommandHandler(), $failureHandler = new FailureErrorHandler()],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel($asyncChannelName),
                PollingMetadata::create('async')->withStopOnError(false)->withExecutionAmountLimit(1)->withErrorChannelName('errorHandler'),
            ],
            enabledModules: [],
        );

        $messaging->sendCommandWithRouting('handler.fail', ['command' => 0]);

        // process message, end up with error message
        $messaging->run($asyncChannelName);
        $this->assertNull($failureHandler->getMessage());

        // handle error message
        $messaging->run($asyncChannelName);
        $this->assertNotNull($failureHandler->getMessage());
    }

    public function test_requeing_when_error_channel_throws_exception_with_in_memory_channel()
    {
        $asyncChannelName = 'async';
        $errorChannelName = 'errorChannelName';

        $messaging = EcotoneLite::bootstrapFlowTesting(
            [ExampleFailureCommandHandler::class],
            [new ExampleFailureCommandHandler(), 'logger' => $logger = StubLogger::create()],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel($asyncChannelName),
                SimpleMessageChannelBuilder::createExceptionChannel(ExceptionalQueueChannel::createWithExceptionOnSend($errorChannelName)),
                PollingMetadata::create('async')->withStopOnError(false)->withExecutionAmountLimit(1)->withErrorChannelName($errorChannelName),
            ],
            enabledModules: [],
        );

        $originalNessage = StaticMessageBuilder::withPayload('some')->build();
        $messaging->sendCommandWithRouting('handler.fail', ['command' => 0]);

        $messaging->run($asyncChannelName);

        $this->assertNull(ChannelTestSupport::of($messaging)->getMessageChannel($errorChannelName)->receive());
        $requeuedMessage = ChannelTestSupport::of($messaging)->getMessageChannel($asyncChannelName)->receive();
        $this->assertNotNull($requeuedMessage);
        $this->assertNotSame($originalNessage->getHeaders()->getMessageId(), $requeuedMessage->getHeaders()->getMessageId());
    }

    public function test_finish_when_no_messages(): void
    {
        $inputChannelName = 'async_channel';
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [SuccessServiceActivator::class],
            [new SuccessServiceActivator()],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel($inputChannelName)],
            enabledModules: [],
        );

        $ecotoneTestSupport->sendDirectToChannel('handle_channel');

        $ecotoneTestSupport->run($inputChannelName, ExecutionPollingMetadata::createWithFinishWhenNoMessages());

        $this->assertSame(
            1,
            $ecotoneTestSupport->sendQueryWithRouting('get_number_of_calls')
        );
        $this->assertNull(
            ChannelTestSupport::of($ecotoneTestSupport)->getMessageChannel($inputChannelName)->receive()
        );
    }

    public function test_finish_when_no_messages_with_more_messages(): void
    {
        $inputChannelName = 'async_channel';
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [SuccessServiceActivator::class],
            [new SuccessServiceActivator()],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel($inputChannelName)],
            enabledModules: [],
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
            ChannelTestSupport::of($ecotoneTestSupport)->getMessageChannel($inputChannelName)->receive()
        );
    }

    private function createPollingConsumer(
        string $inputChannelName,
        QueueChannel $inputChannel,
        MessageHandlerBuilder $messageHandler,
        bool $stopOnFailure = false
    ): FlowTestSupport {
        return ComponentTestBuilder::create(configuration: BootstrapConfiguration::createWithDefaults()->withEnabledModulePackageNames([])->addExtensionObject(\Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false)))
            ->withChannel(SimpleMessageChannelBuilder::create($inputChannelName, $inputChannel))
            ->withPollingMetadata(
                PollingMetadata::create($messageHandler->getEndpointId())
                    ->withTestingSetup(stopOnError: $stopOnFailure)
            )
            ->withMessageHandler(
                $messageHandler
                    ->withInputChannelName($inputChannelName)
            )
            ->build();
    }

    private function createPollingConsumerWithCustomConfiguration(array $channels, MessageHandlerBuilder $messageHandler, PollingMetadata  $pollingMetadata): FlowTestSupport
    {
        $componentTest = ComponentTestBuilder::create(configuration: BootstrapConfiguration::createWithDefaults()->withEnabledModulePackageNames([])->addExtensionObject(\Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false)))
            ->withPollingMetadata($pollingMetadata);
        foreach ($channels as $channelName => $channel) {
            $componentTest = $componentTest->withChannel(SimpleMessageChannelBuilder::create($channelName, $channel));
        }

        return $componentTest
            ->withMessageHandler($messageHandler)
            ->build();
    }
}
