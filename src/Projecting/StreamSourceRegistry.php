<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

use RuntimeException;

final class StreamSourceRegistry
{
    /**
     * @param StreamSource[] $userlandSources
     */
    public function __construct(
        private array $userlandSources,
        private array $builtinSources,
    ) {
    }

    public function getFor(string $projectionName): StreamSource
    {
        foreach ($this->userlandSources as $source) {
            if ($source->canHandle($projectionName)) {
                return $source;
            }
        }

        foreach ($this->builtinSources as $source) {
            if ($source->canHandle($projectionName)) {
                return $source;
            }
        }

        throw new RuntimeException("No stream source found for projection: {$projectionName}");
    }
}
