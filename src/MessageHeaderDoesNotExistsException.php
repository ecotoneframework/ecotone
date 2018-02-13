<?php

namespace SimplyCodedSoftware\IntegrationMessaging;

/**
 * Class MessageHeaderDoesNotExistsException
 * @package SimplyCodedSoftware\IntegrationMessaging\Exception
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MessageHeaderDoesNotExistsException extends MessagingException
{
    /**
     * @inheritDoc
     */
    protected static function errorCode(): int
    {
        return MessagingException::MESSAGE_HEADER_NOT_AVAILABLE_EXCEPTION;
    }
}