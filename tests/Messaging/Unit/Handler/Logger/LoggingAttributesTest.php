<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Logger;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\LogAfter;
use Ecotone\Api\Attribute\LogBefore;
use Ecotone\Api\Attribute\LogError;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Logging\LoggingLevel;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use RuntimeException;
use Stringable;

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class LoggingAttributesTest extends TestCase
{
    public function test_log_before_records_a_log_entry_before_the_handler_runs(): void
    {
        $logger = new RecordingLogger();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [LogBeforeHandler::class],
            ['logger' => $logger, new LogBeforeHandler()],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $ecotone->sendCommandWithRouting('logBefore.handle', 'some-payload');

        $matching = $logger->recordsWithMessage('some-payload');
        $this->assertCount(1, $matching);
        $this->assertSame(LogLevel::INFO, $matching[0]['level']);
    }

    public function test_log_after_records_a_log_entry_after_the_handler_runs(): void
    {
        $logger = new RecordingLogger();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [LogAfterHandler::class],
            ['logger' => $logger, new LogAfterHandler()],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $ecotone->sendQueryWithRouting('logAfter.handle', 'some-payload');

        $matching = $logger->recordsWithMessage('reply-some-payload');
        $this->assertCount(1, $matching);
        $this->assertSame(LogLevel::INFO, $matching[0]['level']);
    }

    public function test_log_before_with_full_message_includes_headers_in_the_context(): void
    {
        $logger = new RecordingLogger();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [LogBeforeFullMessageHandler::class],
            ['logger' => $logger, new LogBeforeFullMessageHandler()],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $ecotone->sendCommandWithRouting('logBeforeFull.handle', 'some-payload');

        $matching = $logger->recordsWithMessage('some-payload');
        $this->assertCount(1, $matching);
        $this->assertArrayHasKey('headers', $matching[0]['context']);
    }

    public function test_log_error_records_the_exception_and_rethrows_it(): void
    {
        $logger = new RecordingLogger();
        $ecotone = EcotoneLite::bootstrapFlowTesting(
            [LogErrorHandler::class],
            ['logger' => $logger, new LogErrorHandler()],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        try {
            $ecotone->sendCommandWithRouting('logError.handle', 'some-payload');
            $this->fail('Expected exception was not thrown');
        } catch (RuntimeException $exception) {
            $this->assertSame('handler failed', $exception->getMessage());
        }

        $matching = $logger->recordsWithMessage('handler failed');
        $this->assertCount(1, $matching);
        $this->assertSame(LogLevel::CRITICAL, $matching[0]['level']);
        $this->assertInstanceOf(RuntimeException::class, $matching[0]['context']['exception']);
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class RecordingLogger extends AbstractLogger
{
    public array $records = [];

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
    }

    public function recordsWithMessage(string $message): array
    {
        return array_values(array_filter($this->records, fn (array $record) => $record['message'] === $message));
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class LogBeforeHandler
{
    #[LogBefore(LoggingLevel::INFO)]
    #[CommandHandler('logBefore.handle')]
    public function handle(string $payload): void
    {
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class LogAfterHandler
{
    #[LogAfter(LoggingLevel::INFO)]
    #[QueryHandler('logAfter.handle')]
    public function handle(string $payload): string
    {
        return 'reply-' . $payload;
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class LogBeforeFullMessageHandler
{
    #[LogBefore(LoggingLevel::INFO, true)]
    #[CommandHandler('logBeforeFull.handle')]
    public function handle(string $payload): void
    {
    }
}

/**
 * licence Apache-2.0
 *
 * @internal
 */
final class LogErrorHandler
{
    #[LogError]
    #[CommandHandler('logError.handle')]
    public function handle(string $payload): void
    {
        throw new RuntimeException('handler failed');
    }
}
