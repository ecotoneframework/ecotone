<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Api\Projecting;

use Attribute;
use Ecotone\Messaging\Attribute\StreamBasedSource;

#[Attribute(Attribute::TARGET_CLASS)]
class Projection extends StreamBasedSource
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
