<?php

namespace Ecotone\Messaging\Handler\Logger;

/**
 * licence Apache-2.0
 */
abstract class Logger
{
    public string $logLevel;
    public bool $logFullMessage;

    public function __construct(string $logLevel = LoggingLevel::INFO, bool $logFullMessage = false)
    {
        $this->logLevel       = $logLevel;
        $this->logFullMessage = $logFullMessage;
    }

    public function getLogLevel(): string
    {
        return $this->logLevel;
    }

    public function isLogFullMessage(): bool
    {
        return $this->logFullMessage;
    }
}
