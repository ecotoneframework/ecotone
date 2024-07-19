<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Logger;

use Ecotone\Messaging\Attribute\MessageGateway;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Attribute\PropagateHeaders;
use Ecotone\Messaging\Message;
use Throwable;

/**
 * licence Apache-2.0
 */
interface LoggingGateway
{
    #[MessageGateway(LoggingService::INFO_LOGGING_CHANNEL)]
    #[PropagateHeaders(false)]
    public function info(
        #[Payload] string                                              $text,
        #[Header(LoggingService::CONTEXT_MESSAGE_HEADER)] ?Message     $message = null,
        #[Header(LoggingService::CONTEXT_EXCEPTION_HEADER)] ?Throwable $exception = null,
        #[Header(LoggingService::CONTEXT_DATA_HEADER)] array           $contextData = [],
    ): void;

    #[MessageGateway(LoggingService::ERROR_LOGGING_CHANNEL)]
    #[PropagateHeaders(false)]
    public function error(
        #[Payload] string                                              $text,
        #[Header(LoggingService::CONTEXT_MESSAGE_HEADER)] Message      $message,
        #[Header(LoggingService::CONTEXT_EXCEPTION_HEADER)] ?Throwable $exception = null,
        #[Header(LoggingService::CONTEXT_DATA_HEADER)] array           $contextData = [],
    ): void;
}
