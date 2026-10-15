<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Api\Projecting;

use Attribute;

#[Attribute]
final class ProjectionStateGateway
{
    public function __construct(private string $projectionName)
    {
    }

    public function getProjectionName(): string
    {
        return $this->projectionName;
    }
}
