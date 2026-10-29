<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Logger;

use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Psr\Log\LogLevel;

/**
 * Class LogLevel
 * @package Ecotone\Messaging\Handler\Logger
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class LoggingLevel extends LogLevel
{
    private ?string $level;
    private bool $logFullMessage;

    /**
     * LogLevel constructor.
     * @param string $logLevel
     * @param bool $logFullMessage
     * @throws MessagingException
     */
    private function __construct(string $logLevel, bool $logFullMessage)
    {
        $this->initialize($logLevel);
        $this->logFullMessage = $logFullMessage;
    }

    /**
     * @param string $logLevel
     * @param bool $logFullMessage
     * @return LoggingLevel
     * @throws MessagingException
     */
    public static function create(string $logLevel, bool $logFullMessage): self
    {
        return new self($logLevel, $logFullMessage);
    }

    public static function createDebug(): self
    {
        return new self(self::DEBUG, false);
    }

    public static function createDebugWithFullMessage(): self
    {
        return new self(self::DEBUG, true);
    }

    /**
     * @param string $logLevel
     * @throws MessagingException
     */
    private function initialize(string $logLevel): void
    {
        if (! in_array($logLevel, [LogLevel::DEBUG, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::EMERGENCY, LogLevel::ERROR, LogLevel::INFO, LogLevel::NOTICE, LogLevel::WARNING])) {
            throw InvalidArgumentException::create("Wrong log level {$logLevel} passed. Check " . LoggingLevel::class . ' for possible log levels');
        }

        $this->level = $logLevel;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    /**
     * @return bool
     */
    public function isFullMessageLog(): bool
    {
        return $this->logFullMessage;
    }

    public function __toString()
    {
        return $this->level;
    }
}
