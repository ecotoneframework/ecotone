<?php

namespace Messaging;
use Messaging\MessagingExceptionCode;

/**
 * Class MessagingException
 * @package Messaging\Exception
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class MessagingException extends \Exception implements MessagingExceptionCode
{
    /**
     * @param string $message
     * @return MessagingException
     */
    public static function create(string $message) : self
    {
        return new static($message, static::errorCode());
    }

    /**
     * @return int
     */
    protected static abstract function errorCode() : int;
}