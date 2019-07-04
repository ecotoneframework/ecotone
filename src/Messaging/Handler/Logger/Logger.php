<?php


namespace SimplyCodedSoftware\Messaging\Handler\Logger;


abstract class Logger
{
    /**
     * @var string
     */
    public $logLevel = LoggingLevel::DEBUG;
    /**
     * @var bool
     */
    public $logFullMessage = false;
}