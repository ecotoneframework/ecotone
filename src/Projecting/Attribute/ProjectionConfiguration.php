<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Attribute;

use Attribute;

/**
 * This attribute allows configure additional projection configuration.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class ProjectionConfiguration
{
    public function __construct(
        public readonly bool $automaticInitialization = true,
    ) {
    }
}
