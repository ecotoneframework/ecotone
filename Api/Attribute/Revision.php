<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;

#[Attribute]
/**
 * licence Apache-2.0
 */
class Revision
{
    public readonly int $revision;

    public function __construct(int $revision)
    {
        $this->revision = $revision;
    }
}
