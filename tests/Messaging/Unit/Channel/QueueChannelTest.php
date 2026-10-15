<?php

namespace Test\Ecotone\Messaging\Unit\Channel;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\FinalFailureStrategy;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
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
    public function test_sending_and_receiving_message_in_first_in_first_out_order()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [],
            [],
            configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('rawQueue', delayable: false))
                ->withEnabledModulePackageNames([]),
        );

        $ecotoneLite->sendDirectToChannel('rawQueue', 'a');
        $ecotoneLite->sendDirectToChannel('rawQueue', 'b');

        $this->assertSame('a', $ecotoneLite->receiveMessageFrom('rawQueue')->getPayload());
        $this->assertSame('b', $ecotoneLite->receiveMessageFrom('rawQueue')->getPayload());
    }

    public function test_returning_null_when_queue_is_empty()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [],
            [],
            configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('rawQueue', delayable: false))
                ->withEnabledModulePackageNames([]),
        );

        $this->assertNull($ecotoneLite->receiveMessageFrom('rawQueue'));
    }

    public function test_resending_message_back_to_queue_on_failure(): void
    {
        $failureService = $this->getFailureService();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$failureService::class],
            [$failureService],
            configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(\Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false))->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel(
                'async',
                finalFailureStrategy: FinalFailureStrategy::RESEND
            ))
                ->withEnabledModulePackageNames([])
        );

        $ecotoneLite
            ->sendCommandWithRouting('call.service', 'fail_1')
            ->sendCommandWithRouting('call.service', 'success_1')
            ->run('async', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 10, executionTimeLimitInMilliseconds: 1000000, stopOnError: false));

        $this->assertEquals(['fail_1', 'success_1', 'fail_1'], $failureService->messages);
    }

    public function test_releasing_message_back_to_queue_on_failure(): void
    {
        $failureService = $this->getFailureService();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$failureService::class],
            [$failureService],
            configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(\Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false))->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel(
                'async',
                finalFailureStrategy: FinalFailureStrategy::RELEASE
            ))
                ->withEnabledModulePackageNames([])
        );

        $ecotoneLite
            ->sendCommandWithRouting('call.service', 'fail_1')
            ->sendCommandWithRouting('call.service', 'success_1')
            ->run('async', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 10, executionTimeLimitInMilliseconds: 1000000, stopOnError: false));

        $this->assertEquals(['fail_1', 'fail_1', 'success_1'], $failureService->messages);
    }

    public function test_ignore_message_back_to_queue_on_failure(): void
    {
        $failureService = $this->getFailureService();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$failureService::class],
            [$failureService],
            configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(\Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false))->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel(
                'async',
                finalFailureStrategy: FinalFailureStrategy::IGNORE
            ))
                ->withEnabledModulePackageNames([])
        );

        $ecotoneLite
            ->sendCommandWithRouting('call.service', 'fail_1')
            ->sendCommandWithRouting('call.service', 'success_1')
            ->run('async', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 10, executionTimeLimitInMilliseconds: 1000000, stopOnError: false));

        $this->assertEquals(['fail_1', 'success_1'], $failureService->messages);
    }

    public function test_stop_consumption_on_failure(): void
    {
        $failureService = $this->getFailureService();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [$failureService::class],
            [$failureService],
            configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(\Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false))->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel(
                'async',
                finalFailureStrategy: FinalFailureStrategy::STOP
            ))
                ->withEnabledModulePackageNames([])
        );

        $ecotoneLite
            ->sendCommandWithRouting('call.service', 'fail_1')
            ->sendCommandWithRouting('call.service', 'success_1');

        try {
            $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 10, executionTimeLimitInMilliseconds: 1000000, stopOnError: false));
        } catch (Exception) {
            // we are expecting exception here
        }

        $this->assertEquals(['fail_1'], $failureService->messages);

        try {
            $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 10, executionTimeLimitInMilliseconds: 1000000, stopOnError: false));
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
            configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(\Ecotone\Api\ExtensionObject\InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false))->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel(
                'async',
                finalFailureStrategy: FinalFailureStrategy::RESEND
            ))
                ->withEnabledModulePackageNames([])
        );

        $ecotoneLite->sendCommandWithRouting('executionChannel', 'some_1');
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
