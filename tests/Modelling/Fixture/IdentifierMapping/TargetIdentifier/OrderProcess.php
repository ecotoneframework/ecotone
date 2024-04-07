<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\IdentifierMapping\TargetIdentifier;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\Attribute\Saga;

#[Saga]
final class OrderProcess
{
    #[Identifier]
    private string $orderId;

    private function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    #[EventHandler]
    public static function createWhen(OrderStarted $event): self
    {
        return new self($event->id);
    }

    #[EventHandler]
    public function createWhenOnExisting(OrderStarted $event): void
    {
    }

    #[Asynchronous('async')]
    #[EventHandler(endpointId: 'createWhenAsync')]
    public static function createWhenAsync(OrderStartedAsynchronous $event): self
    {
        return new self($event->id);
    }

    #[Asynchronous('async')]
    #[EventHandler(endpointId: 'createWhenAsync2')]
    public function createWhenAsyncOnExisting(OrderStartedAsynchronous $event): void
    {
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }
}
