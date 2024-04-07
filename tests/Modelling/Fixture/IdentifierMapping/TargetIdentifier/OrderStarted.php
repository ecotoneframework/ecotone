<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\IdentifierMapping\TargetIdentifier;

use Ecotone\Modelling\Attribute\TargetIdentifier;

final class OrderStarted
{
    public function __construct(
        #[TargetIdentifier('orderId')] public string $id
    ) {

    }
}
