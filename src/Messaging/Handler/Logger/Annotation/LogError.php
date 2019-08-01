<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Logger\Annotation;

use Ecotone\Messaging\Handler\Logger\Logger;
use Ecotone\Messaging\Handler\Logger\LoggingLevel;

/**
 * Class LogError
 * @package Ecotone\Messaging\Handler\Logger\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class LogError extends Logger
{
    /**
     * @var string
     */
    public $logLevel = LoggingLevel::CRITICAL;
}