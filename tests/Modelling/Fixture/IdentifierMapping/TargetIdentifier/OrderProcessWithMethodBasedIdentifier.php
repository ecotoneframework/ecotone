<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\IdentifierMapping\TargetIdentifier;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\IdentifierMethod;
use Ecotone\Modelling\Attribute\Saga;

#[Saga]
/**
 * licence Apache-2.0
 */
final class OrderProcessWithMethodBasedIdentifier
{
    private string $id;

    private function __construct(string $orderId)
    {
        $this->id = $orderId;
    }

    #[EventHandler]
    public static function createWhen(OrderStarted $event): self
    {
        return new self($event->id);
    }

    #[Asynchronous('async')]
    #[EventHandler(endpointId: 'createWhenAsync')]
    public static function createWhenAsync(OrderStartedAsynchronous $event): self
    {
        return new self($event->id);
    }

    #[IdentifierMethod('orderId')]
    public function getOrderId(): string
    {
        return $this->id;
    }
}
