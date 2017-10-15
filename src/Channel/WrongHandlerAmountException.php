<?php

namespace Messaging\Channel;

use Messaging\MessagingException;

/**
 * Class WrongHandlerAmountException
 * @package Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class WrongHandlerAmountException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::WRONG_HANDLER_AMOUNT;
    }
}