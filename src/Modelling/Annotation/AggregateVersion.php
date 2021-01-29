<?php
declare(strict_types=1);

namespace Ecotone\Modelling\Annotation;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
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