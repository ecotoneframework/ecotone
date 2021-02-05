<?php


namespace Ecotone\Modelling\Attribute;

use Ecotone\Modelling\DistributedBus;

#[\Attribute]
class Distributed
{
    private string $distributionReference;

    public function __construct(string $distributionReference = DistributedBus::class)
    {
        $this->distributionReference = $distributionReference;
    }

    public function getDistributionReference(): string
    {
        return $this->distributionReference;
    }
}