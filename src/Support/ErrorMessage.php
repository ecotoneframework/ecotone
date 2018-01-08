<?php

namespace SimplyCodedSoftware\Messaging\Support;

use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessageHeaders;

/**
 * Class ErrorMessage where payload is thrown exception
 * @package SimplyCodedSoftware\Messaging\Support
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

    private function setOriginalMessage(Message $message) : void
    {
        $this->originalMessage = $message;
    }
}