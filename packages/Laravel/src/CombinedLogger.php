<?php

namespace Ecotone\Laravel;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class CombinedLogger extends AbstractLogger implements LoggerInterface
{
    private LoggerInterface $applicationLogger;

    private LoggerInterface $consoleLogger;

    public function __construct(LoggerInterface $applicationLogger, LoggerInterface $consoleLogger)
    {
        $this->applicationLogger = $applicationLogger;
        $this->consoleLogger = $consoleLogger;
    }

    public function log($level, $message, array $context = []): void
    {
        $this->applicationLogger->log($level, $message, $context);
        $this->consoleLogger->log($level, $message, $context);
    }
}
