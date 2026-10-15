<?php

namespace Ecotone\Api\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class Environment
{
    /**
     * @var string[]
     */
    public readonly array $names;

    public function __construct(array $environments)
    {
        $this->names = $environments;
    }
}
