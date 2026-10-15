<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
/**
 * licence Apache-2.0
 */
class Version
{
    public readonly bool $autoIncrease;

    public function __construct(bool $autoIncrease = true)
    {
        $this->autoIncrease = $autoIncrease;
    }
}
