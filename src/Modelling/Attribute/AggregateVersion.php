<?php
declare(strict_types=1);

namespace Ecotone\Modelling\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY|\Attribute::TARGET_PARAMETER)]
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