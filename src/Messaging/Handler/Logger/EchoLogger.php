<?php

namespace Ecotone\Messaging\Handler\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * Class EchoLogger
 * @package Ecotone\Messaging\Handler\Logger
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class EchoLogger extends AbstractLogger
{
    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = []): void
    {
        $color = "\033[32m";
        if (in_array($level, [LogLevel::ALERT, LogLevel::EMERGENCY, LogLevel::CRITICAL, LogLevel::ERROR])) {
            $color = "\033[31m";
        }

        echo "{$color}{$level}:\033[0m {$message}\n";
    }
}
