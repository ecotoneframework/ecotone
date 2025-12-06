<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Throwable;

/**
 * licence Apache-2.0
 */
trait ConsumerInterceptorTrait
{
    public function onStartup(): void
    {
    }

    public function onShutdown(): void
    {
    }

    public function shouldBeStopped(): bool
    {
        return false;
    }

    public function shouldBeThrown(Throwable $exception): bool
    {
        return false;
    }

    public function preRun(): void
    {

    }

    /**
     * Called after each run
     */
    public function postRun(?Throwable $unhandledFailure): void
    {
    }

    /**
     * Called after each sending message to request channel
     */
    public function postSend(): void
    {
    }
}
