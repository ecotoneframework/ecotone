<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Config\InstantRetry;

use Ecotone\Messaging\Execution\ExecutionFlag;
use Ecotone\Messaging\Execution\ExecutionScope;

/**
 * licence Apache-2.0
 */
final class RetryStatusTracker
{
    public function __construct(
        private readonly bool $isWrappedByRetryInterceptor
    ) {

    }

    public function markAsWrapped(): void
    {
        $this->flag()->isRaised = true;
    }

    public function markAsUnwrapped(): void
    {
        $this->flag()->isRaised = false;
    }

    public function isCurrentlyWrappedByRetry(): bool
    {
        return $this->flag()->isRaised;
    }

    private function flag(): ExecutionFlag
    {
        return ExecutionScope::stateOf($this, fn () => new ExecutionFlag($this->isWrappedByRetryInterceptor));
    }
}
