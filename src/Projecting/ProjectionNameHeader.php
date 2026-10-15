<?php

declare(strict_types=1);

namespace Ecotone\Projecting;

/**
 * licence Apache-2.0
 */
interface ProjectionNameHeader
{
    /**
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    public function applyTo(array $headers, string $projectionName): array;
}
