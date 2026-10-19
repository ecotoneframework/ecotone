<?php

declare(strict_types=1);

namespace Ecotone\Api\Tracing;

use Ecotone\Api\Scheduling\DatePoint;

/**
 * licence Apache-2.0
 */
final class SpanEvent
{
    /**
     * @param array<string, scalar|null> $attributes
     */
    public function __construct(
        public readonly string $name,
        public readonly array $attributes,
        public readonly DatePoint $at,
    ) {
    }
}
