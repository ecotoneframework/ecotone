<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Projecting\InMemory;

use Ecotone\EventSourcing\EventStore\InMemoryEventStore;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Projecting\Config\ProjectionComponentBuilder;
use Ecotone\Projecting\StreamSource;

class InMemoryEventStoreStreamSourceBuilder implements ProjectionComponentBuilder
{
    public function __construct(
        private ?array $projectionNames = null,
        private ?string $streamName = null,
        private ?string $partitionHeader = null
    ) {
    }

    public function canHandle(string $projectionName, string $component): bool
    {
        return $component === StreamSource::class
            && ($this->projectionNames === null || in_array($projectionName, $this->projectionNames, true));
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        return new Definition(
            InMemoryEventStoreStreamSource::class,
            [
                Reference::to(InMemoryEventStore::class),
                $this->streamName,
                $this->partitionHeader,
            ]
        );
    }
}
