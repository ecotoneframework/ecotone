<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Config\InstantRetry;

/**
 * licence Apache-2.0
 */
final class RetryStatusTracker
{
    public function __construct(
        private bool $isWrappedByRetryInterceptor
    ) {

    }

    public function markAsWrapped(): void
    {
        $this->isWrappedByRetryInterceptor = true;
    }

    public function markAsUnwrapped(): void
    {
        $this->isWrappedByRetryInterceptor = false;
    }

    public function isCurrentlyWrappedByRetry(): bool
    {
        return $this->isWrappedByRetryInterceptor;
    }
}
