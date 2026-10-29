<?php

namespace Ecotone\Modelling\Attribute;

use Attribute;
use Ecotone\Modelling\DistributedBus;

#[Attribute]
/**
 * licence Apache-2.0
 */
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
