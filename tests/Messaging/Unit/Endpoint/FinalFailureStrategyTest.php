<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Endpoint;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Messaging\Channel\PollableChannel\InMemory\InMemoryAcknowledgeStatus;
use Ecotone\Messaging\Channel\PollableChannel\InMemory\InMemoryQueueAcknowledgeInterceptor;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Endpoint\AcknowledgementCallback;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Endpoint\FinalFailureStrategy;
use Ecotone\Messaging\Endpoint\PollingConsumer\RejectMessageException;
use Ecotone\Messaging\Message;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
#[CoversClass(FinalFailureStrategy::class)]
final class FinalFailureStrategyTest extends TestCase
{
    public function test_reject_failure_strategy_rejects_message_on_exception()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [FailingService::class],
            [$service = new FailingService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel(messageChannelName: 'async', finalFailureStrategy: FinalFailureStrategy::IGNORE),
            ]
        );

        $ecotoneTestSupport->sendDirectToChannel('executionChannel', 'some');
        $ecotoneTestSupport->run('async', ExecutionPollingMetadata::createWithTestingSetup(failAtError: false));

        $this->assertSame(
            InMemoryAcknowledgeStatus::IGNORED,
            $service->getMessage()->getHeaders()->get(InMemoryQueueAcknowledgeInterceptor::ECOTONE_IN_MEMORY_QUEUE_ACK)->getStatus()
        );
    }

    public function test_requeue_failure_strategy_requeues_message_on_exception()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [FailingService::class],
            [$service = new FailingService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel(messageChannelName: 'async', finalFailureStrategy: FinalFailureStrategy::RESEND),
            ]
        );

        $ecotoneTestSupport->sendDirectToChannel('executionChannel', 'some');
        $ecotoneTestSupport->run('async', ExecutionPollingMetadata::createWithTestingSetup(failAtError: false));

        $this->assertSame(
            InMemoryAcknowledgeStatus::RESENT,
            $service->getMessage()->getHeaders()->get(InMemoryQueueAcknowledgeInterceptor::ECOTONE_IN_MEMORY_QUEUE_ACK)->getStatus()
        );
    }

    public function test_stop_failure_strategy_stops_consumer_on_exception()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [FailingService::class],
            [$service = new FailingService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel(messageChannelName: 'async', finalFailureStrategy: FinalFailureStrategy::STOP),
            ]
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Service failed');

        $ecotoneTestSupport->sendDirectToChannel('executionChannel', 'some');
        $ecotoneTestSupport->run('async', ExecutionPollingMetadata::createWithTestingSetup(failAtError: false));
    }

    public function test_successful_processing_always_acknowledges_message()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [SuccessService::class],
            [$service = new SuccessService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel(messageChannelName: 'async', finalFailureStrategy: FinalFailureStrategy::IGNORE),
            ]
        );

        $ecotoneTestSupport->sendDirectToChannel('executionChannel', 'some');
        $ecotoneTestSupport->run('async', ExecutionPollingMetadata::createWithTestingSetup(failAtError: false));

        $this->assertSame(
            InMemoryAcknowledgeStatus::ACKED,
            $service->getMessage()->getHeaders()->get(InMemoryQueueAcknowledgeInterceptor::ECOTONE_IN_MEMORY_QUEUE_ACK)->getStatus()
        );
    }

    public function test_reject_message_exception_triggers_reject_strategy()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [RejectingService::class],
            [$service = new RejectingService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel(messageChannelName: 'async', finalFailureStrategy: FinalFailureStrategy::IGNORE),
            ]
        );

        $ecotoneTestSupport->sendDirectToChannel('executionChannel', 'some');
        $ecotoneTestSupport->run('async', ExecutionPollingMetadata::createWithTestingSetup(failAtError: false));

        $this->assertSame(
            InMemoryAcknowledgeStatus::IGNORED,
            $service->getMessage()->getHeaders()->get(InMemoryQueueAcknowledgeInterceptor::ECOTONE_IN_MEMORY_QUEUE_ACK)->getStatus()
        );
    }

    public function test_setting_execution_metadata_to_stop_on_error()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [RejectingService::class],
            [$service = new RejectingService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel(messageChannelName: 'async', finalFailureStrategy: FinalFailureStrategy::IGNORE),
            ]
        );

        $ecotoneTestSupport->sendDirectToChannel('executionChannel', 'some');
        $ecotoneTestSupport->run('async', ExecutionPollingMetadata::createWithTestingSetup(failAtError: true));

        $this->assertSame(
            InMemoryAcknowledgeStatus::IGNORED,
            $service->getMessage()->getHeaders()->get(InMemoryQueueAcknowledgeInterceptor::ECOTONE_IN_MEMORY_QUEUE_ACK)->getStatus()
        );
    }

    public function test_manual_ack_service_acknowledges_message_manually()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [ManualAckService::class],
            [$service = new ManualAckService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel(messageChannelName: 'async', finalFailureStrategy: FinalFailureStrategy::STOP, isAutoAcked: false),
            ]
        );

        $ecotoneTestSupport->sendDirectToChannel('executionChannel', 'some');
        $ecotoneTestSupport->run('async', ExecutionPollingMetadata::createWithTestingSetup(failAtError: false));

        $this->assertSame(
            InMemoryAcknowledgeStatus::IGNORED,
            $service->getMessage()->getHeaders()->get(InMemoryQueueAcknowledgeInterceptor::ECOTONE_IN_MEMORY_QUEUE_ACK)->getStatus()
        );
    }

    public function test_auto_ack_disabled_and_fails()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [FailingService::class],
            [$service = new FailingService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel(messageChannelName: 'async', finalFailureStrategy: FinalFailureStrategy::RESEND, isAutoAcked: false),
            ]
        );

        $ecotoneTestSupport->sendDirectToChannel('executionChannel', 'some');
        $ecotoneTestSupport->run('async', ExecutionPollingMetadata::createWithTestingSetup(failAtError: false));

        $this->assertSame(
            InMemoryAcknowledgeStatus::RESENT,
            $service->getMessage()->getHeaders()->get(InMemoryQueueAcknowledgeInterceptor::ECOTONE_IN_MEMORY_QUEUE_ACK)->getStatus()
        );
    }
}

class FailingService
{
    private Message $message;

    #[Asynchronous('async')]
    #[ServiceActivator('executionChannel', 'failing_service')]
    public function handle(Message $message): void
    {
        $this->message = $message;

        throw new Exception('Service failed');
    }

    public function getMessage(): Message
    {
        return $this->message;
    }
}

class SuccessService
{
    private Message $message;

    #[Asynchronous('async')]
    #[ServiceActivator('executionChannel', 'success_service')]
    public function handle(Message $message): void
    {
        $this->message = $message;

        // Success - do nothing
    }

    public function getMessage(): Message
    {
        return $this->message;
    }
}

class RejectingService
{
    private Message $message;

    #[Asynchronous('async')]
    #[ServiceActivator('executionChannel', 'rejecting_service')]
    public function handle(Message $message): void
    {
        $this->message = $message;

        throw new RejectMessageException('Reject this message');
    }

    public function getMessage(): Message
    {
        return $this->message;
    }
}

class ManualAckService
{
    private Message $message;

    #[Asynchronous('async')]
    #[ServiceActivator('executionChannel', 'manual_ack_service')]
    public function handle(Message $message): void
    {
        $this->message = $message;

        /** @var AcknowledgementCallback $ackCallback */
        $ackCallback = $message->getHeaders()->get(InMemoryQueueAcknowledgeInterceptor::ECOTONE_IN_MEMORY_QUEUE_ACK);
        $ackCallback->reject();
    }

    public function getMessage(): Message
    {
        return $this->message;
    }
}
