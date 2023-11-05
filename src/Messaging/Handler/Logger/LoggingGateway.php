<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Logger;

use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Message;
use Throwable;

interface LoggingGateway
{
    #[MessageGateway(LoggingService::INFO_LOGGING_CHANNEL)]
    public function info(
        #[Payload] string $text,
        #[Header(LoggingService::CONTEXT_MESSAGE_HEADER)] Message $message,
        #[Header(LoggingService::CONTEXT_EXCEPTION_HEADER)] ?Throwable $exception = null
    ): void;

    #[MessageGateway(LoggingService::ERROR_LOGGING_CHANNEL)]
    public function error(
        #[Payload] string $text,
        #[Header(LoggingService::CONTEXT_MESSAGE_HEADER)] Message $message,
        #[Header(LoggingService::CONTEXT_EXCEPTION_HEADER)] ?Throwable $exception = null
    ): void;
}
