<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Workflow\Saga\Command;

final class TakePayment
{
    public function __construct(
        public string $orderId
    ) {
    }
}
