<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\SagaWithMultipleActions;

final class SagaCreatedEvent
{
    public string $orderId;

    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }
}
