<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Retry;

use Ecotone\Messaging\Attribute\ServiceActivator;
use Ecotone\Messaging\Message;

/**
 * licence Apache-2.0
 */
class ErrorChannelHandler
{
    private bool $errorHandled = false;

    #[ServiceActivator('customErrorChannel')]
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
