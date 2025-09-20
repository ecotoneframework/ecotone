<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\InMemory;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\MessagingContainerBuilder;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\DefinedObjectWrapper;
use Ecotone\Projecting\Config\ProjectionComponentBuilder;
use Ecotone\Projecting\StreamSource;

class InMemoryStreamSourceBuilder extends InMemoryStreamSource implements ProjectionComponentBuilder, DefinedObject
{
    public function __construct(private ?array $projectionNames = null, ?string $partitionField = null, array $events = [])
    {
        parent::__construct($partitionField, $events);
    }

    public function compile(MessagingContainerBuilder $builder): Definition|Reference
    {
        return new DefinedObjectWrapper($this);
    }

    public function canHandle(string $projectionName, string $component): bool
    {
        return $component === StreamSource::class && ($this->projectionNames === null || in_array($projectionName, $this->projectionNames, true));
    }

    public function getDefinition(): Definition
    {
        return new Definition(InMemoryStreamSource::class);
    }
}
