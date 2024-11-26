<?php

/**
 * Created by PhpStorm.
 * User: dgafka
 * Date: 14.10.17
 * Time: 14:29
 */

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\MessagingException;

/**
 * licence Apache-2.0
 */
class MessageDispatchingException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::MESSAGE_DISPATCHING_EXCEPTION;
    }
}
