<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\Interceptor;

use Ecotone\Messaging\Endpoint\ConsumerInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Throwable;

class FinishWhenNoMessagesInterceptor implements ConsumerInterceptor
{
    private bool $shouldBeStopped = false;

    /**
     * @inheritDoc
     */
    public function onStartup(): void
    {
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
    public function postSend(MethodInvocation $methodInvocation): mixed
    {
        $this->shouldBeStopped = false;

        return $methodInvocation->proceed();
    }

    public function isInterestedInPostSend(): bool
    {
        return true;
    }
}
