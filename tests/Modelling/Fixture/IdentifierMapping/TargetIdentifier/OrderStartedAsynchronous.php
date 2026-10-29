<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\IdentifierMapping\TargetIdentifier;

use Ecotone\Modelling\Attribute\TargetIdentifier;

/**
 * licence Apache-2.0
 */
final class OrderStartedAsynchronous
{
    public function __construct(
        #[TargetIdentifier('orderId')] public string $id
    ) {

    }
}
