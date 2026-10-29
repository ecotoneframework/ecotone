<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Endpoint\PollingConsumer;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use PHPUnit\Framework\TestCase;

/**
 * @licence Apache-2.0
 * @internal
 */
final class MessageConsumerSignalHandlingTest extends TestCase
{
    public function test_consumer_with_execution_time_limit_can_handle_signals_gracefully(): void
    {
        $messageHandler = new TestMessageHandler();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [TestMessageHandler::class],
            [$messageHandler],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );

        $ecotoneLite->sendDirectToChannel('handle_channel', 'test-message');

        $ecotoneLite->run(
            'async',
            ExecutionPollingMetadata::createWithTestingSetup(
                amountOfMessagesToHandle: 1,
                maxExecutionTimeInMilliseconds: 1000
            )
        );

        $this->assertEquals(['test-message'], $messageHandler->getProcessedMessages());
    }

    public function test_consumer_with_execution_time_limit_stops_gracefully_when_time_limit_reached(): void
    {
        $messageHandler = new TestMessageHandler();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [TestMessageHandler::class],
            [$messageHandler],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );

        $ecotoneLite->sendDirectToChannel('handle_channel', 'message-1');
        $ecotoneLite->sendDirectToChannel('handle_channel', 'message-2');
        $ecotoneLite->sendDirectToChannel('handle_channel', 'message-3');

        $ecotoneLite->run(
            'async',
            ExecutionPollingMetadata::createWithTestingSetup(
                amountOfMessagesToHandle: 10,
                maxExecutionTimeInMilliseconds: 50
            )
        );

        $this->assertGreaterThanOrEqual(1, count($messageHandler->getProcessedMessages()));
        $this->assertLessThanOrEqual(3, count($messageHandler->getProcessedMessages()));
    }

    public function test_consumer_without_execution_time_limit_processes_all_messages(): void
    {
        $messageHandler = new TestMessageHandler();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [TestMessageHandler::class],
            [$messageHandler],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );

        $ecotoneLite->sendDirectToChannel('handle_channel', 'message-1');
        $ecotoneLite->sendDirectToChannel('handle_channel', 'message-2');
        $ecotoneLite->sendDirectToChannel('handle_channel', 'message-3');

        $ecotoneLite->run(
            'async',
            ExecutionPollingMetadata::createWithTestingSetup(
                amountOfMessagesToHandle: 3,
                maxExecutionTimeInMilliseconds: 0
            )
        );

        $this->assertEquals(['message-1', 'message-2', 'message-3'], $messageHandler->getProcessedMessages());
    }

    public function test_consumer_stops_after_current_message_when_signal_sent_during_processing(): void
    {
        $signalHandler = new SignalSendingMessageHandler();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [SignalSendingMessageHandler::class],
            [$signalHandler],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );

        $ecotoneLite->sendDirectToChannel('handle_channel', 'message-1');
        $ecotoneLite->sendDirectToChannel('handle_channel', 'message-2');
        $ecotoneLite->sendDirectToChannel('handle_channel', 'message-3');

        $ecotoneLite->run(
            'async',
            ExecutionPollingMetadata::createWithTestingSetup(
                amountOfMessagesToHandle: 10,
                maxExecutionTimeInMilliseconds: 30000
            )
        );

        $this->assertEquals(['message-1'], $signalHandler->getProcessedMessages());
    }

    public function test_consumer_stops_after_current_message_when_signal_sent_during_processing_with_defaults(): void
    {
        $signalHandler = new SignalSendingMessageHandler();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [SignalSendingMessageHandler::class],
            [$signalHandler],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );

        $ecotoneLite->sendDirectToChannel('handle_channel', 'message-1');
        $ecotoneLite->sendDirectToChannel('handle_channel', 'message-2');
        $ecotoneLite->sendDirectToChannel('handle_channel', 'message-3');

        $ecotoneLite->run(
            'async',
            ExecutionPollingMetadata::createWithDefaults(),
        );

        $this->assertEquals(['message-1'], $signalHandler->getProcessedMessages());
    }
}

class TestMessageHandler
{
    private array $processedMessages = [];

    #[Asynchronous('async')]
    #[ServiceActivator('handle_channel', 'test_handler')]
    public function handle(string $message): void
    {
        $this->processedMessages[] = $message;
        usleep(10000);
    }

    public function getProcessedMessages(): array
    {
        return $this->processedMessages;
    }
}

class SignalSendingMessageHandler
{
    private array $processedMessages = [];

    #[Asynchronous('async')]
    #[ServiceActivator('handle_channel', 'signal_handler')]
    public function handle(string $message): void
    {
        $this->processedMessages[] = $message;

        if (count($this->processedMessages) === 1) {
            usleep(100000);
            posix_kill(posix_getpid(), SIGTERM);
            usleep(100000);
        }
    }

    public function getProcessedMessages(): array
    {
        return $this->processedMessages;
    }
}
