<?php

declare(strict_types=1);

namespace Ecotone\Projecting;

/**
 * licence Apache-2.0
 */
final class OpenCoreProjectionNameHeader implements ProjectionNameHeader
{
    public function applyTo(array $headers, string $projectionName): array
    {
        return $headers;
    }
}
