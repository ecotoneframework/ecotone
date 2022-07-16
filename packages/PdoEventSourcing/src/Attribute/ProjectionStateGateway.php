<?php

namespace Ecotone\EventSourcing\Attribute;

use Attribute;
use Ecotone\EventSourcing\ProjectionManager;

#[Attribute]
final class ProjectionStateGateway
{
    public function __construct(private string $projectionName, private string $projectioManagerReference = ProjectionManager::class)
    {
    }

    public function getProjectionName(): string
    {
        return $this->projectionName;
    }

    public function getProjectioManagerReference(): string
    {
        return $this->projectioManagerReference;
    }
}
