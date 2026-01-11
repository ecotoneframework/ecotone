<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting;

use RuntimeException;

final class PartitionProviderRegistry
{
    /**
     * @param PartitionProvider[] $providers
     */
    public function __construct(
        private array $providers,
    ) {
    }

    public function getPartitionProviderFor(string $projectionName): PartitionProvider
    {
        foreach ($this->providers as $provider) {
            if ($provider->canHandle($projectionName)) {
                return $provider;
            }
        }

        throw new RuntimeException("No partition provider found for projection: {$projectionName}");
    }
}
