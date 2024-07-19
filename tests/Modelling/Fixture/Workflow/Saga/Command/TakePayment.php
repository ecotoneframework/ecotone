<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Workflow\Saga\Command;

/**
 * licence Apache-2.0
 */
final class TakePayment
{
    public function __construct(
        public string $orderId
    ) {
    }
}
