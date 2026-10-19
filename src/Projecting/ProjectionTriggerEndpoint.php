<?php

declare(strict_types=1);

namespace Ecotone\Projecting;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
final class ProjectionTriggerEndpoint
{
    public function __construct(public readonly string $projectionName)
    {
    }
}
