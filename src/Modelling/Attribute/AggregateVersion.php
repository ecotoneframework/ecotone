<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Attribute;

use Attribute;

/**
 * @deprecated Ecotone 2.0 will drop this attribute. Use #[Version] instead
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
/**
 * licence Apache-2.0
 */
class AggregateVersion
{
    private bool $autoIncrease;

    public function __construct(bool $autoIncrease = true)
    {
        $this->autoIncrease = $autoIncrease;
    }

    public function isAutoIncreased(): bool
    {
        return $this->autoIncrease;
    }
}
