<?php

namespace Ecotone\Messaging\Support;

use Ecotone\Messaging\Handler\Recoverability\ErrorContext;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Throwable;

/**
 * Class ErrorMessage where payload is thrown exception
 * @package Ecotone\Messaging\Support
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
final class ErrorMessage implements Message
{
    private function __construct(
        private Message $message
    ) {
    }

    public static function createFromMessage(Message $message): self
    {
        if (! self::isErrorMessage($message)) {
            throw MessagingException::create('Trying to create error message from message that is not generic message.');
        }

        return new self($message);
    }

    public static function isErrorMessage(Message $message): bool
    {
        foreach (ErrorContext::WHOLE_ERROR_CONTEXT as $errorContextKey) {
            if (! $message->getHeaders()->containsKey($errorContextKey)) {
                return false;
            }
        }

        return true;
    }

    public static function create(Message $message, Throwable $cause): self
    {
        return new self(
            MessageBuilder::fromMessage($message)
                ->setHeader(ErrorContext::EXCEPTION_CLASS, get_class($cause))
                ->setHeader(ErrorContext::EXCEPTION_MESSAGE, $cause->getMessage())
                ->setHeader(ErrorContext::EXCEPTION_STACKTRACE, $cause->getTraceAsString())
                ->setHeader(ErrorContext::EXCEPTION_FILE, $cause->getFile())
                ->setHeader(ErrorContext::EXCEPTION_LINE, $cause->getLine())
                ->setHeader(ErrorContext::EXCEPTION_CODE, $cause->getCode())
                ->build()
        );
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(): MessageHeaders
    {
        return $this->message->getHeaders();
    }

    /**
     * @inheritDoc
     */
    public function getPayload(): mixed
    {
        return $this->message->getPayload();
    }

    public function getErrorContext(): ErrorContext
    {
        return ErrorContext::fromHeaders($this->getHeaders()->headers());
    }

    public function getExceptionClass(): string
    {
        return $this->getHeaders()->get(ErrorContext::EXCEPTION_CLASS);
    }

    public function getExceptionMessage(): string
    {
        return $this->getHeaders()->get(ErrorContext::EXCEPTION_MESSAGE);
    }

    public function getExceptionStackTrace(): string
    {
        return $this->getHeaders()->get(ErrorContext::EXCEPTION_STACKTRACE);
    }

    public function getExceptionFile(): string
    {
        return $this->getHeaders()->get(ErrorContext::EXCEPTION_FILE);
    }

    public function getExceptionLine(): string
    {
        return $this->getHeaders()->get(ErrorContext::EXCEPTION_LINE);
    }

    public function getExceptionCode(): string
    {
        return $this->getHeaders()->get(ErrorContext::EXCEPTION_CODE);
    }
}
