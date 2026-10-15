<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test\Configuration;

use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Messaging\Scheduling\Clock;
use Ecotone\Test\StaticPsrClock;

/**
 * licence Apache-2.0
 */
final class PinnedTestClockInterceptor
{
    public function __construct(private EcotoneClockInterface $clock)
    {
    }

    public function handleAtPinnedTime(MethodInvocation $methodInvocation): mixed
    {
        $psrClock = $this->clock instanceof Clock ? $this->clock->internalClock() : null;
        if (! $psrClock instanceof StaticPsrClock) {
            return $methodInvocation->proceed();
        }

        return $psrClock->executeAtPinnedTime(fn () => $methodInvocation->proceed());
    }
}
