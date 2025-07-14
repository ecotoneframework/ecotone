<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Attribute;

use Attribute;

/**
 * licence Enterprise
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class InstantRetry
{
    /**
     * @param int $retryTimes Number of retry attempts
     * @param array $exceptions Array of exception class names to retry on (empty means all exceptions)
     */
    public function __construct(
        public readonly int $retryTimes = 3,
        public readonly array $exceptions = []
    ) {
    }
}
