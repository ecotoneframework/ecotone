<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Logger\Annotation;

use Ecotone\Messaging\Handler\Logger\Logger;
use Ecotone\Messaging\Handler\Logger\LoggingLevel;

#[\Attribute(\Attribute::TARGET_METHOD)]
class LogError extends Logger
{
    public string $logLevel = LoggingLevel::CRITICAL;
}