<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\ErrorHandler;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\ExtensionObject\ErrorHandlerConfiguration;
use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\InstantRetryConfiguration;
use Ecotone\Api\ExtensionObject\RetryTemplateBuilder;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Handler\MessageHandlingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Test\StubLogger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * licence Apache-2.0
 * @internal
 */
final class DelayedRetryBackOffTest extends TestCase
{
    public function test_zero_back_off_retries_within_one_run_and_ends_in_dead_letter(): void
    {
        $sender = new class () {
            public int $attempts = 0;

            #[Asynchronous('async')]
            #[EventHandler('order.completed', endpointId: 'sendOrderConfirmation')]
            public function send(string $orderId): void
            {
                $this->attempts++;
                throw new RuntimeException('SMTP connection refused');
            }
        };

        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [$sender::class],
            [$sender],
            ServiceConfiguration::createWithDefaults()
                ->withDefaultErrorChannel('errorChannel')
                ->withExtensionObjects([
                    ErrorHandlerConfiguration::createWithDeadLetterChannel('errorChannel', RetryTemplateBuilder::fixedBackOff(0)->maxRetries(3), 'deadLetter'),
                    InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
                    SimpleMessageChannelBuilder::createQueueChannel('deadLetter'),
                    SimpleMessageChannelBuilder::createQueueChannel('async'),
                ])
                    ->withEnabledModulePackageNames([]),
        );

        $ecotone->publishEventWithRouting('order.completed', 'order-1');
        $ecotone->run('async', ExecutionPollingMetadata::createWithTestingSetup(stopOnError: false));

        $this->assertSame(4, $sender->attempts);
        $this->assertNotNull($ecotone->getMessageChannel('deadLetter')->receive());
    }

    public function test_exponential_back_off_accepts_zero_initial_delay(): void
    {
        $this->assertSame(0, RetryTemplateBuilder::exponentialBackOff(0, 2)->maxRetries(2)->build()->calculateNextDelay(2));
    }

    public function test_negative_initial_delay_is_rejected_with_the_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Retry initial delay must be 0 or greater, got -1 ms');

        RetryTemplateBuilder::fixedBackOff(-1);
    }

    public function test_dead_letter_log_counts_failed_deliveries_as_initial_delivery_plus_retries(): void
    {
        $logger = StubLogger::create();
        $ecotone = $this->bootstrapAlwaysFailingSender($logger, deadLetter: true);

        $ecotone->publishEventWithRouting('order.completed', 'order-1');
        $ecotone->run('async', ExecutionPollingMetadata::createWithTestingSetup(stopOnError: false));

        $deadLetterLines = array_values(array_filter($logger->getError(), fn (string $line) => str_contains($line, 'dead letter')));
        $this->assertCount(1, $deadLetterLines);
        $this->assertMatchesRegularExpression('/^Sending message `[^`]+` to dead letter channel after 4 failed deliveries \\(1 initial \\+ 3 retries\\)\\. Due to: SMTP connection refused$/', $deadLetterLines[0]);
    }

    public function test_exhausted_retries_without_dead_letter_name_deliveries_and_retries(): void
    {
        $ecotone = $this->bootstrapAlwaysFailingSender(StubLogger::create(), deadLetter: false, maxRetries: 1);

        $ecotone->publishEventWithRouting('order.completed', 'order-1');

        $this->expectException(MessageHandlingException::class);
        $this->expectExceptionMessage('Message handling failed on channel `async` after 2 failed deliveries (1 initial + 1 retry). RuntimeException: SMTP connection refused');

        $ecotone->run('async', ExecutionPollingMetadata::createWithTestingSetup(stopOnError: false));
    }

    private function bootstrapAlwaysFailingSender(StubLogger $logger, bool $deadLetter, int $maxRetries = 3)
    {
        $sender = new class () {
            #[Asynchronous('async')]
            #[EventHandler('order.completed', endpointId: 'sendOrderConfirmation')]
            public function send(string $orderId): void
            {
                throw new RuntimeException('SMTP connection refused');
            }
        };

        return EcotoneLite::bootstrapFlowTesting(
            [$sender::class],
            [$sender, 'logger' => $logger],
            ServiceConfiguration::createWithDefaults()
                ->withDefaultErrorChannel('errorChannel')
                ->withExtensionObjects([
                    $deadLetter
                        ? ErrorHandlerConfiguration::createWithDeadLetterChannel('errorChannel', RetryTemplateBuilder::fixedBackOff(0)->maxRetries($maxRetries), 'deadLetter')
                        : ErrorHandlerConfiguration::create('errorChannel', RetryTemplateBuilder::fixedBackOff(0)->maxRetries($maxRetries)),
                    InstantRetryConfiguration::createWithDefaults()->withAsynchronousEndpointsRetry(false),
                    SimpleMessageChannelBuilder::createQueueChannel('deadLetter'),
                    SimpleMessageChannelBuilder::createQueueChannel('async'),
                ])
                    ->withEnabledModulePackageNames([]),
        );
    }
}
