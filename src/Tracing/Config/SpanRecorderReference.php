<?php

declare(strict_types=1);

namespace Ecotone\Tracing\Config;

/**
 * licence Apache-2.0
 */
final class SpanRecorderReference
{
    public function __construct(public readonly string $referenceName)
    {
    }
}
