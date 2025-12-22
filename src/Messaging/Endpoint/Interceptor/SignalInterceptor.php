<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\Interceptor;

use Ecotone\Messaging\Endpoint\ConsumerInterceptor;
use Ecotone\Messaging\Endpoint\ConsumerInterceptorTrait;

/**
 * licence Apache-2.0
 */
class SignalInterceptor implements ConsumerInterceptor
{
    use ConsumerInterceptorTrait;

    public function __construct(private PcntlTerminationListener $pcntlTerminationListener)
    {
    }

    public function onStartup(): void
    {
        $this->pcntlTerminationListener->enable();
    }

    public function onShutdown(): void
    {
        $this->pcntlTerminationListener->disable();
    }

    public function shouldBeStopped(): bool
    {
        return $this->pcntlTerminationListener->shouldTerminate();
    }
}
