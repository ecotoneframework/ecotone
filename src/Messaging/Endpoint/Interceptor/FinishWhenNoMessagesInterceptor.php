<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\Interceptor;

use Ecotone\Messaging\Endpoint\ConsumerInterceptor;
use Ecotone\Messaging\Endpoint\ConsumerInterceptorTrait;

/**
 * licence Apache-2.0
 */
class FinishWhenNoMessagesInterceptor implements ConsumerInterceptor
{
    use ConsumerInterceptorTrait;
    private bool $shouldBeStopped = false;

    public function onStartup(): void
    {
        $this->shouldBeStopped = false;
    }

    public function shouldBeStopped(): bool
    {
        return $this->shouldBeStopped;
    }

    public function postReceive(bool $messageLeftWaiting): void
    {
        $this->shouldBeStopped = ! $messageLeftWaiting;
    }
}
