<?php

namespace Ecotone\Api\Attribute;

use Attribute;
use Ecotone\Api\Gateway\DistributedBus;

#[Attribute]
/**
 * licence Apache-2.0
 */
class Distributed
{
    public readonly string $distributionReference;

    public function __construct(string $distributionReference = DistributedBus::class)
    {
        $this->distributionReference = $distributionReference;
    }
}
