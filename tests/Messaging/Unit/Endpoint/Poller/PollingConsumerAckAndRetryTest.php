<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Endpoint\Poller;

use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\InstantRetryConfiguration;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\RetryTemplateBuilder;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\ExceptionalQueueChannel;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class PollingConsumerAckAndRetryTest extends TestCase
{
    public function test_retry_template_does_not_intercept_an_exception_thrown_while_handling_a_message(): void
    {
        $handler = new AlwaysThrowingHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [AlwaysThrowingHandler::class],
            [$handler],
            ServiceConfiguration::createWithDefaults()->withExtensionObjects([
                InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
                SimpleMessageChannelBuilder::createQueueChannel(AlwaysThrowingHandler::CHANNEL),
                PollingMetadata::create(AlwaysThrowingHandler::ENDPOINT_ID)
                    ->withTestingSetup(stopOnError: true)
                    ->withConnectionRetryTemplate(RetryTemplateBuilder::fixedBackOff(1)->maxRetries(1)),
            ])
                ->withEnabledModulePackageNames([]),
        );

        $ecotone->sendDirectToChannel(AlwaysThrowingHandler::CHANNEL, 'somePayload');

        $exceptionThrown = false;
        try {
            $ecotone->run(AlwaysThrowingHandler::ENDPOINT_ID);
        } catch (RuntimeException $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
        $this->assertSame(1, $handler->calledTimes);
    }

    public function test_retry_template_retries_an_exception_thrown_while_receiving_a_message(): void
    {
        $handler = new NeverCalledHandler();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [NeverCalledHandler::class],
            [$handler],
            ServiceConfiguration::createWithDefaults()->withExtensionObjects([
                SimpleMessageChannelBuilder::createExceptionChannel(ExceptionalQueueChannel::createWithExceptionOnReceive(NeverCalledHandler::CHANNEL)),
                PollingMetadata::create(NeverCalledHandler::ENDPOINT_ID)
                    ->withTestingSetup(stopOnError: false)
                    ->withConnectionRetryTemplate(RetryTemplateBuilder::fixedBackOff(1)->maxRetries(2)),
            ])
                ->withEnabledModulePackageNames([]),
        );

        try {
            $ecotone->run(NeverCalledHandler::ENDPOINT_ID);
        } catch (RuntimeException $e) {
        }

        $this->assertFalse($handler->wasCalled);
    }

    public function test_a_consumer_without_its_own_connection_retry_recovers_from_failed_receives_with_the_default_one(): void
    {
        $handler = new class () {
            public int $calledTimes = 0;

            #[InternalHandler('defaultConnectionRetry.channel', endpointId: 'defaultConnectionRetry.endpoint')]
            public function handle(string $payload): void
            {
                $this->calledTimes++;
            }
        };
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$handler::class],
            [$handler],
            ServiceConfiguration::createWithDefaults()->withExtensionObjects([
                SimpleMessageChannelBuilder::createExceptionChannel(ExceptionalQueueChannel::createWithExceptionOnReceive('defaultConnectionRetry.channel', 3)),
            ])
                ->withEnabledModulePackageNames([]),
        );

        $ecotone->sendDirectToChannel('defaultConnectionRetry.channel', 'somePayload');
        $ecotone->run('defaultConnectionRetry.endpoint', ExecutionPollingMetadata::createWithTestingSetup(handledMessageLimit: 1, executionTimeLimitInMilliseconds: 10000, stopOnError: false));

        $this->assertSame(1, $handler->calledTimes);
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class AlwaysThrowingHandler
{
    public const CHANNEL = 'pollingConsumerRetry.channel';
    public const ENDPOINT_ID = 'pollingConsumerRetry.endpoint';

    public int $calledTimes = 0;

    #[InternalHandler(self::CHANNEL, endpointId: self::ENDPOINT_ID)]
    public function handle(string $payload): void
    {
        $this->calledTimes++;

        throw new RuntimeException('error during handling');
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class NeverCalledHandler
{
    public const CHANNEL = 'pollingConsumerReceiveRetry.channel';
    public const ENDPOINT_ID = 'pollingConsumerReceiveRetry.endpoint';

    public bool $wasCalled = false;

    #[InternalHandler(self::CHANNEL, endpointId: self::ENDPOINT_ID)]
    public function handle(string $payload): void
    {
        $this->wasCalled = true;
    }
}
