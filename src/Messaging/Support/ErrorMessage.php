<?php

namespace Ecotone\Messaging\Support;

use Ecotone\Messaging\Handler\Recoverability\ErrorContext;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
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

    public static function create(Message $message, Throwable $cause): self
    {
        return new self(
            MessageBuilder::fromMessage($message)
                ->setHeader(ErrorContext::EXCEPTION, $cause)
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

    public function getException(): Throwable
    {
        return $this->getHeaders()->get(ErrorContext::EXCEPTION);
    }
}
