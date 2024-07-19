<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Workflow\Saga\Event;

/**
 * licence Apache-2.0
 */
final class OrderProcessSagaStarted
{
    public function __construct(
        public string $orderId
    ) {
    }
}
