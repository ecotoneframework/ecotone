<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Logger;

use Ecotone\Messaging\Message;
use Psr\Log\LoggerInterface;
use Stringable;

/**
 * licence Apache-2.0
 */
interface LoggingGateway extends LoggerInterface
{
    public function info(Stringable|string $message, array|Message|null $context = [], array $additionalContext = []): void;
    public function error(Stringable|string $message, array|Message|null $context = [], array $additionalContext = []): void;
    public function critical(Stringable|string $message, array|Message|null $context = [], array $additionalContext = []): void;
}
