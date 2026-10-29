<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Logger\Annotation;

use Attribute;
use Ecotone\Messaging\Handler\Logger\Logger;
use Ecotone\Messaging\Handler\Logger\LoggingLevel;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class LogError extends Logger
{
    public string $logLevel = LoggingLevel::CRITICAL;

    public function __construct(string $logLevel = LoggingLevel::CRITICAL, bool $logFullMessage = false)
    {
        $this->logLevel       = $logLevel;
        $this->logFullMessage = $logFullMessage;
    }
}
