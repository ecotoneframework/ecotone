<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Logger;

use Ecotone\Messaging\Message;
use Psr\Log\LogLevel;
use Stringable;

/**
 * licence Apache-2.0
 */
final class StubLoggingGateway extends LoggingService
{
    private array $logs = [];

    public static function create(): self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function log($level, Stringable|string $message, Message|array $context = [], array $additionalContext = []): void
    {
        $this->logs[] = new LogRecord($level, $message, $context);
    }

    public function getError(): array
    {
        return $this->getLogs(LogLevel::ERROR);
    }

    public function getInfo(): array
    {
        return $this->getLogs(LogLevel::INFO);
    }

    public function getLogs(?string $withLogLevel = null): array
    {
        if ($withLogLevel) {
            return array_filter($this->logs, fn (LogRecord $logRecord) => $logRecord->level === $withLogLevel);
        }
        return $this->logs;
    }
}

/**
 * licence Apache-2.0
 * @internal
 */
class LogRecord
{
    public function __construct(public string $level, public string $message, public array|Message $context)
    {
    }
}
