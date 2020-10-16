<?php


namespace Ecotone\Messaging\Handler\Logger;

use Psr\Log\AbstractLogger;

/**
 * Class EchoLogger
 * @package Ecotone\Messaging\Handler\Logger
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EchoLogger extends AbstractLogger
{
    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = array()): void
    {
        echo "{$level}: {$message}\n";
    }
}