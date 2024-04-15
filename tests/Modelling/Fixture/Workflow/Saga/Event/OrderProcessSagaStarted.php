<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Workflow\Saga\Event;

final class OrderProcessSagaStarted
{
    public function __construct(
        public string $orderId
    ) {
    }
}
