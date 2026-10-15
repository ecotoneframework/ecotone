<?php

namespace Ecotone\Api\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
/**
 * licence Apache-2.0
 */
class TargetIdentifier
{
    public readonly string $identifierName;

    public function __construct(string $identifierName = '')
    {
        $this->identifierName = $identifierName;
    }
}
