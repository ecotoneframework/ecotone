<?php


namespace Ecotone\Messaging\Handler\Logger;


abstract class Logger
{
    /**
     * @var string
     */
    public $logLevel = LoggingLevel::INFO;
    /**
     * @var bool
     */
    public $logFullMessage = false;
}