<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\InMemory;

use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Projecting\Config\ProjectionComponentBuilder;
use Ecotone\Projecting\ProjectionStateStorage;

class InMemoryProjectionStateStorageBuilder implements ProjectionComponentBuilder
{
    public function __construct(private ?array $projectionNames = null)
    {
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        return new Definition(InMemoryProjectionStateStorage::class);
    }

    public function canHandle(string $projectionName, string $component): bool
    {
        return $component === ProjectionStateStorage::class && ($this->projectionNames === null || in_array($projectionName, $this->projectionNames, true));
    }
}
