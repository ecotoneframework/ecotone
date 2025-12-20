<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Attribute;

use Attribute;
use Ecotone\Messaging\Attribute\StreamBasedSource;

#[Attribute(Attribute::TARGET_CLASS)]
class ProjectionV2 extends StreamBasedSource
{
    public function __construct(
        public readonly string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
