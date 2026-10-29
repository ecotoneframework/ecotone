<?php

namespace Test\Ecotone\Messaging\Unit\Channel;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Endpoint\FinalFailureStrategy;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\Attribute\CommandHandler;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Class QueueChannelTest
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class QueueChannelTest extends TestCase
{
    public function test_sending_and_receiving_message_in_last_in_first_out_order()
    {
        $queueChannel = QueueChannel::create();

        $firstMessage = MessageBuilder::withPayload('a')->build();
        $secondMessage = MessageBuilder::withPayload('b')->build();

        $queueChannel->send($firstMessage);
        $queueChannel->send($secondMessage);

        $this->assertEquals($firstMessage, $queueChannel->receive());
        $this->assertEquals($secondMessage, $queueChannel->receive());
    }

    public function test_returning_null_when_queue_is_empty()
    {
        $queueChannel = QueueChannel::create();

        $this->assertNull($queueChannel->receive());
    }

    public function test_resending_message_back_to_queue_on_failure(): void
    {
        $failureService = $this->getFailureService();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$failureService::class],
            [$failureService],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel(
                    'async',
                    finalFailureStrategy: FinalFailureStrategy::RESEND
                ),
            ]
        );

        $ecotoneLite
            ->sendCommandWithRoutingKey('call.service', 'fail_1')
            ->sendCommandWithRoutingKey('call.service', 'success_1')
            ->run('async', ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 10, maxExecutionTimeInMilliseconds: 1000000, failAtError: false));

        $this->assertEquals(['fail_1', 'success_1', 'fail_1'], $failureService->messages);
    }

    public function test_releasing_message_back_to_queue_on_failure(): void
    {
        $failureService = $this->getFailureService();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$failureService::class],
            [$failureService],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel(
                    'async',
                    finalFailureStrategy: FinalFailureStrategy::RELEASE
                ),
            ]
        );

        $ecotoneLite
            ->sendCommandWithRoutingKey('call.service', 'fail_1')
            ->sendCommandWithRoutingKey('call.service', 'success_1')
            ->run('async', ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 10, maxExecutionTimeInMilliseconds: 1000000, failAtError: false));

        $this->assertEquals(['fail_1', 'fail_1', 'success_1'], $failureService->messages);
    }

    public function test_ignore_message_back_to_queue_on_failure(): void
    {
        $failureService = $this->getFailureService();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$failureService::class],
            [$failureService],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel(
                    'async',
                    finalFailureStrategy: FinalFailureStrategy::IGNORE
                ),
            ]
        );

        $ecotoneLite
            ->sendCommandWithRoutingKey('call.service', 'fail_1')
            ->sendCommandWithRoutingKey('call.service', 'success_1')
            ->run('async', ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 10, maxExecutionTimeInMilliseconds: 1000000, failAtError: false));

        $this->assertEquals(['fail_1', 'success_1'], $failureService->messages);
    }

    public function test_stop_consumption_on_failure(): void
    {
        $failureService = $this->getFailureService();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$failureService::class],
            [$failureService],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel(
                    'async',
                    finalFailureStrategy: FinalFailureStrategy::STOP
                ),
            ]
        );

        $ecotoneLite
            ->sendCommandWithRoutingKey('call.service', 'fail_1')
            ->sendCommandWithRoutingKey('call.service', 'success_1');

        try {
            $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 10, maxExecutionTimeInMilliseconds: 1000000, failAtError: false));
        } catch (Exception) {
            // we are expecting exception here
        }

        $this->assertEquals(['fail_1'], $failureService->messages);

        try {
            $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup(amountOfMessagesToHandle: 10, maxExecutionTimeInMilliseconds: 1000000, failAtError: false));
        } catch (Exception) {
            // we are expecting exception here
        }

        $this->assertEquals(['fail_1', 'fail_1', 'success_1'], $failureService->messages);
    }

    public function test_message_is_retried_with_on_memory_queue(): void
    {
        $failureService = new class () {
            #[Asynchronous('async'), CommandHandler('executionChannel', 'execute')]
            public function execute(string $data): void
            {
                throw new Exception('We are failing here');
            }
        };

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$failureService::class],
            [$failureService],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel(
                    'async',
                    finalFailureStrategy: FinalFailureStrategy::RESEND
                ),
            ]
        );

        $ecotoneLite->sendCommandWithRoutingKey('executionChannel', 'some_1');
        try {
            $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup());
            self::fail('We are expecting exception here');
        } catch (Exception) {
            // we are expecting exception here
        }

        // The exception should cause the message to be retried
        self::expectException(Exception::class);

        $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup());
    }

    public function getFailureService()
    {
        $failureService = new class () {
            public $messages = [];

            #[Asynchronous('async')]
            #[CommandHandler('call.service', endpointId: 'call.service.endpoint')]
            public function handle(string $message): void
            {
                try {
                    if (strpos($message, 'fail_') !== false) {
                        if (in_array($message, $this->messages)) {
                            return;
                        }

                        throw new InvalidArgumentException('test');
                    }
                } finally {
                    $this->messages[] = $message;
                }
            }
        };
        return $failureService;
    }
}
