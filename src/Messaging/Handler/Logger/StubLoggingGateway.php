<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Logger;

use Ecotone\Messaging\Message;
use Throwable;

/**
 * licence Apache-2.0
 */
final class StubLoggingGateway implements LoggingGateway
{
    private array $info = [];
    private array $critical = [];

    public static function create(): self
    {
        return new self();
    }

    public function info(
        string                                              $text,
        ?Message     $message = null,
        ?Throwable $exception = null,
        array           $contextData = [],
    ): void {
        $this->info[] = $text;
    }

    public function error(
        string                                              $text,
        Message      $message,
        ?Throwable $exception = null,
        array           $contextData = [],
    ): void {
        $this->critical[] = $text;
    }

    public function getCritical(): array
    {
        return $this->critical;
    }

    public function getInfo(): array
    {
        return $this->info;
    }
}
