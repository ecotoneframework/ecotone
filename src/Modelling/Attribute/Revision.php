<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Attribute;

use Attribute;

#[Attribute]
/**
 * licence Apache-2.0
 */
class Revision
{
    private int $revision;

    public function __construct(int $revision)
    {
        $this->revision = $revision;
    }

    public function getRevision(): int
    {
        return $this->revision;
    }
}
