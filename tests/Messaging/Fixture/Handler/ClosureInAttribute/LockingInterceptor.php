<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute;

use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Interceptor\MethodInvocation;

/**
 * licence Apache-2.0
 */
final class LockingInterceptor
{
    private array $lockedResources = [];

    #[Around(pointcut: Locking::class)]
    public function lock(MethodInvocation $methodInvocation, Locking $locking): mixed
    {
        $this->lockedResources[] = ($locking->resource)();

        return $methodInvocation->proceed();
    }

    public function getLockedResources(): array
    {
        return $this->lockedResources;
    }
}
