<?php

namespace Ecotone\Messaging\Handler\Recoverability;

use Ecotone\Messaging\MessageHeaders;

class ErrorContext
{
    const EXCEPTION_STACKTRACE = "exception-stacktrace";
    const EXCEPTION_FILE = "exception-file";
    const EXCEPTION_LINE = "exception-line";
    const EXCEPTION_CODE = "exception-code";
    const EXCEPTION_MESSAGE = "exception-message";

    private string $messageId;
    private int $failedTimestamp;
    private string $stackTrace;
    private string $file;
    private string $line;
    private string $code;
    private string $message;

    private function __construct(string $messageId, int $failedTimestamp, string $message, string $stackTrace, string $code, string $file, string $line)
    {
        $this->messageId = $messageId;
        $this->failedTimestamp = $failedTimestamp;
        $this->stackTrace = $stackTrace;
        $this->file       = $file;
        $this->line       = $line;
        $this->code       = $code;
        $this->message    = $message;
    }

    public static function fromHeaders(array $messageHeaders) : self
    {
        return new self(
            $messageHeaders[MessageHeaders::MESSAGE_ID],
            $messageHeaders[MessageHeaders::TIMESTAMP],
            $messageHeaders[self::EXCEPTION_MESSAGE],
            $messageHeaders[self::EXCEPTION_STACKTRACE],
            $messageHeaders[self::EXCEPTION_CODE],
            $messageHeaders[self::EXCEPTION_FILE],
            $messageHeaders[self::EXCEPTION_LINE]
        );
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function getFailedTimestamp(): int
    {
        return $this->failedTimestamp;
    }

    public function getStackTrace(): string
    {
        return $this->stackTrace;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getLine(): string
    {
        return $this->line;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}