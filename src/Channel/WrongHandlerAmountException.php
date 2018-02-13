<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Channel;

use SimplyCodedSoftware\IntegrationMessaging\MessagingException;

/**
 * Class WrongHandlerAmountException
 * @package SimplyCodedSoftware\IntegrationMessaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class WrongHandlerAmountException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return self::WRONG_HANDLER_AMOUNT_EXCEPTION;
    }
}