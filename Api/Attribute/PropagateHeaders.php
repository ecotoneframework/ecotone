<?php

namespace Ecotone\Api\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
/**
 * licence Apache-2.0
 */
final class PropagateHeaders
{
    public function __construct(
        public readonly bool $doPropagation = true
    ) {
    }
}
