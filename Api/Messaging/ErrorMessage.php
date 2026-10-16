<?php

namespace Ecotone\Api\Messaging;

use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\GenericMessage;
use Ecotone\Messaging\Support\MessageDescription;
use Throwable;

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
            GenericMessage::create(
                $message->getPayload(),
                MessageHeaders::create([
                    ...$message->getHeaders()->headers(),
                    ErrorContext::EXCEPTION_CLASS => get_class($cause),
                    ErrorContext::EXCEPTION_MESSAGE => $cause->getMessage(),
                    ErrorContext::EXCEPTION_STACKTRACE => $cause->getTraceAsString(),
                    ErrorContext::EXCEPTION_FILE => $cause->getFile(),
                    ErrorContext::EXCEPTION_LINE => $cause->getLine(),
                    ErrorContext::EXCEPTION_CODE => $cause->getCode(),
                ])
            )
        );
    }

    public function getHeaders(): MessageHeaders
    {
        return $this->message->getHeaders();
    }

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

    public function __toString()
    {
        return MessageDescription::of($this);
    }
}
