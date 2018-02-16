<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Support;

use SimplyCodedSoftware\IntegrationMessaging\Message;
use SimplyCodedSoftware\IntegrationMessaging\MessageHeaders;

/**
 * Class ErrorMessage where payload is thrown exception
 * @package SimplyCodedSoftware\IntegrationMessaging\Support
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
final class ErrorMessage extends GenericMessage
{
    /**
     * @var Message|null
     */
    private $originalMessage;

    /**
     * @param \Throwable $exception
     * @param Message $originalMessage
     * @return ErrorMessage
     */
    public static function createWithOriginalMessage(\Throwable $exception, Message $originalMessage) : self
    {
        /** @var ErrorMessage $errorMessage */
        $errorMessage = self::create($exception, MessageHeaders::createEmpty());
        $errorMessage->setOriginalMessage($originalMessage);

        return $errorMessage;
    }

    /**
     * @return Message|null
     */
    public function getOriginalMessage() : ?Message
    {
        return $this->originalMessage;
    }

    /**
     * @param Message $message
     */
    private function setOriginalMessage(Message $message) : void
    {
        $this->originalMessage = $message;
    }
}