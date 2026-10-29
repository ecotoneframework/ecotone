<?php

declare(strict_types=1);

namespace Ecotone\Messaging;

use Ecotone\Messaging\Handler\MessageHandlingException;
use Exception;
use Throwable;

/**
 * Class MessagingException
 * @package Ecotone\Messaging\Exception
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class MessagingException extends Exception
{
    public const LICENSE_EXCEPTION = 1;
    public const RUNTIME_EXCEPTION = 2;
    public const INVALID_MESSAGE_HEADER_EXCEPTION = 100;
    public const MESSAGE_HEADER_NOT_AVAILABLE_EXCEPTION = 101;
    public const INVALID_ARGUMENT_EXCEPTION = 102;
    public const MESSAGING_SERVICE_NOT_AVAILABLE_EXCEPTION = 103;
    public const CONFIGURATION_EXCEPTION = 104;
    public const DESTINATION_RESOLUTION_EXCEPTION = 105;
    public const REFERENCE_NOT_FOUND_EXCEPTION = 106;
    public const MESSAGE_FILTER_THROW_EXCEPTION_ON_DISCARD = 107;
    public const DISTRIBUTED_KEY_IS_NOT_AVAILABLE = 108;

    public const MESSAGE_DELIVERY_EXCEPTION = 200;
    public const MESSAGE_DISPATCHING_EXCEPTION = 201;
    public const WRONG_HANDLER_AMOUNT_EXCEPTION = 202;
    public const MESSAGE_HANDLING_EXCEPTION = 205;
    public const MESSAGE_PUBLISH_EXCEPTION = 206;
    public const DISTRIBUTED_DESTINATION_NOT_FOUND = 207;

    public const WRONG_EXPRESSION_TO_EVALUATE = 300;

    private ?Message $failedMessage = null;
    private ?Throwable $cause = null;

    /**
     * @param string $message
     * @return MessagingException|static
     */
    public static function create(string $message, ?int $errorCode = null): self
    {
        /** @phpstan-ignore-next-line */
        return new static($message, is_null($errorCode) ? static::errorCode() : $errorCode);
    }

    /**
     * @param string $message
     * @param Message $failedMessage
     * @return MessagingException|static
     */
    public static function createWithFailedMessage(string $message, Message $failedMessage): self
    {
        $exception = static::create($message);
        $exception->setFailedMessage($failedMessage);

        return $exception;
    }

    /**
     * @param string $message
     * @param Throwable $throwable
     * @return MessagingException
     */
    public static function createFromPreviousException(string $message, Throwable $throwable): self
    {
        /** @phpstan-ignore-next-line */
        return new static($message, static::errorCode(), $throwable);
    }

    /**
     * @param Throwable $cause
     * @param Message $originalMessage
     * @return MessageHandlingException|static
     */
    public static function fromOtherException(Throwable $cause, Message $originalMessage): MessagingException
    {
        $messageHandlingException = self::createWithFailedMessage($cause->getMessage(), $originalMessage);

        $messageHandlingException->setCausationException($cause);

        return $messageHandlingException;
    }

    /**
     * @return Message|null
     */
    public function getFailedMessage(): ?Message
    {
        return $this->failedMessage;
    }

    /**
     * @return int
     */
    protected static function errorCode(): int
    {
        return self::RUNTIME_EXCEPTION;
    }

    /**
     * @param Message $message
     */
    protected function setFailedMessage(Message $message): void
    {
        $this->failedMessage = $message;
    }

    /**
     * @param Throwable $cause
     */
    protected function setCausationException(Throwable $cause): void
    {
        $this->cause = $cause;
    }

    /**
     * @return null|Throwable
     */
    public function getCause(): ?Throwable
    {
        $cause = $this->cause;
        if ($cause instanceof MessagingException && $cause->getCause()) {
            $cause = $cause->getCause();
        }

        return $cause;
    }
}
