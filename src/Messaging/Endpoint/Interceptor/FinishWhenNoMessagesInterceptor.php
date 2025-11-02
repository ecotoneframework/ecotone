<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\Interceptor;

use Ecotone\Messaging\Endpoint\ConsumerInterceptor;
use Ecotone\Messaging\Scheduling\DatePoint;
use Ecotone\Messaging\Scheduling\Duration;
use Ecotone\Messaging\Scheduling\EcotoneClockInterface;
use Throwable;

/**
 * licence Apache-2.0
 */
class FinishWhenNoMessagesInterceptor implements ConsumerInterceptor
{
    private bool $shouldBeStopped = false;
    private ?DatePoint $lastTimeMessageWasReceived;

    public function __construct(private EcotoneClockInterface $clock)
    {
    }

    /**
     * @inheritDoc
     */
    public function onStartup(): void
    {
        $this->lastTimeMessageWasReceived = $this->clock->now();
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
        if ($this->lastTimeMessageWasReceived->add(Duration::milliseconds(10)) > $this->clock->now()) {
            $this->shouldBeStopped = false;
        }

        return $this->shouldBeStopped;
    }

    /**
     * @inheritDoc
     */
    public function postRun(?Throwable $unhandledFailure): void
    {
    }

    /**
     * @inheritDoc
     */
    public function postSend(): void
    {
        $this->shouldBeStopped = false;
        $this->lastTimeMessageWasReceived = $this->clock->now();
    }
}
