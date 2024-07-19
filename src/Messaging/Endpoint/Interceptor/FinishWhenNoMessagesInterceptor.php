<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\Interceptor;

use Ecotone\Messaging\Endpoint\ConsumerInterceptor;
use Throwable;

/**
 * licence Apache-2.0
 */
class FinishWhenNoMessagesInterceptor implements ConsumerInterceptor
{
    private bool $shouldBeStopped = false;
    private float $lastTimeMessageWasReceived = 0;

    /**
     * @inheritDoc
     */
    public function onStartup(): void
    {
        $this->lastTimeMessageWasReceived = $this->currentTimeInMilliseconds();
    }

    /**
     * @inheritDoc
     */
    public function preRun(): void
    {
        $this->shouldBeStopped = true;
    }

    /**
     * @inheritDoc
     */
    public function shouldBeThrown(Throwable $exception): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function shouldBeStopped(): bool
    {
        /**
         * wait at least 10ms between each message before deciding to finish.
         * Messages can be requeued and we don't want to finish too early
         */
        if ($this->lastTimeMessageWasReceived + 10 > $this->currentTimeInMilliseconds()) {
            $this->shouldBeStopped = false;
        }

        return $this->shouldBeStopped;
    }

    /**
     * @inheritDoc
     */
    public function postRun(): void
    {
    }

    /**
     * @inheritDoc
     */
    public function postSend(): void
    {
        $this->shouldBeStopped = false;
        $this->lastTimeMessageWasReceived = $this->currentTimeInMilliseconds();
    }

    private function currentTimeInMilliseconds(): int|float
    {
        return floor(microtime(true) * 1000);
    }
}
