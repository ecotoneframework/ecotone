<?php
/**
 * Created by PhpStorm.
 * User: dgafka
 * Date: 14.10.17
 * Time: 14:29
 */

namespace SimplyCodedSoftware\IntegrationMessaging\Channel;


use SimplyCodedSoftware\IntegrationMessaging\MessagingException;

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