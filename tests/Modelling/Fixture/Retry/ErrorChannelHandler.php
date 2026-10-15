<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Retry;

use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Messaging\Message;

/**
 * licence Apache-2.0
 */
class ErrorChannelHandler
{
    private bool $errorHandled = false;

    #[InternalHandler('customErrorChannel')]
    public function handle(Message $message): void
    {
        $this->errorHandled = true;
    }

    public function wasErrorHandled(): bool
    {
        return $this->errorHandled;
    }

    public function reset(): void
    {
        $this->errorHandled = false;
    }
}
