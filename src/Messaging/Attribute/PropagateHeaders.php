<?php

namespace Ecotone\Messaging\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
final class PropagateHeaders
{
    public function __construct(
        private bool $doPropagation = true
    ) {

    }

    public function doPropagation(): bool
    {
        return $this->doPropagation;
    }
}
