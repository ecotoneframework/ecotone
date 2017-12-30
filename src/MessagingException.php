<?php

namespace Messaging;

/**
 * Class MessagingException
 * @package Messaging\Exception
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
abstract class MessagingException extends \Exception
{
    const INVALID_MESSAGE_HEADER_EXCEPTION = 100;
    const MESSAGE_HEADER_NOT_AVAILABLE_EXCEPTION = 101;
    const INVALID_ARGUMENT_EXCEPTION = 102;
    const MESSAGING_SERVICE_NOT_AVAILABLE_EXCEPTION = 103;
    const CONFIGURATION_EXCEPTION = 104;
    const DESTINATION_RESOLUTION_EXCEPTION = 105;

    const MESSAGE_DELIVERY_EXCEPTION = 200;
    const MESSAGE_DISPATCHING_EXCEPTION = 201;
    const WRONG_HANDLER_AMOUNT_EXCEPTION = 202;
    const RUN_TIME_MESSAGING_EXCEPTION = 204;
    const MESSAGE_HANDLING_EXCEPTION = 205;

    /**
     * @var Message
     */
    private $failedMessage;
    /**
     * @var \Throwable
     */
    private $cause;

    /**
     * @param string $message
     * @return MessagingException|static
     */
    public static function create(string $message) : self
    {
        return new static($message, static::errorCode());
    }

    /**
     * @param string $message
     * @param Message $failedMessage
     * @return MessagingException|static
     */
    public static function createWithFailedMessage(string $message, Message $failedMessage) : self
    {
        $exception = static::create($message);
        $exception->setFailedMessage($failedMessage);

        return $exception;
    }

    /**
     * @inheritDoc
     */
    public function hasErrorCode(int $errorCode): bool
    {
        return self::errorCode() === $errorCode;
    }

    /**
     * @return Message|null
     */
    public function failedMessage() : ?Message
    {
        return $this->failedMessage;
    }

    /**
     * @return int
     */
    protected abstract static function errorCode() : int;

    /**
     * @param Message $message
     */
    protected function setFailedMessage(Message $message) : void
    {
        $this->failedMessage = $message;
    }

    /**
     * @param \Throwable $cause
     */
    protected function setCausationException(\Throwable $cause) : void
    {
        $this->cause = $cause;
    }
}