<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler;

use Ecotone\Modelling\Attribute\TargetIdentifier;

/**
 * licence Apache-2.0
 */
class OrderWasPlacedWithIdentifierWithTarget
{
    public function __construct(
        #[TargetIdentifier('id')] public string $orderId
    ) {
    }
}
