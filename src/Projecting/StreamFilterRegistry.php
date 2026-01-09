<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

/**
 * Registry that provides stream filters for projections.
 * Each projection can have multiple stream filters.
 */
final class StreamFilterRegistry
{
    /**
     * @param array<string, StreamFilter[]> $filters key is projection name
     */
    public function __construct(
        private array $filters = [],
    ) {
    }

    /**
     * @return StreamFilter[]
     */
    public function provide(string $projectionName): array
    {
        return $this->filters[$projectionName] ?? [];
    }
}
