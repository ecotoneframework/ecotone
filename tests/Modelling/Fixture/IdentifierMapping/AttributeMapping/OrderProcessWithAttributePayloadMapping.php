<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\IdentifierMapping\AttributeMapping;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\Attribute\Saga;

#[Saga]
final class OrderProcessWithAttributePayloadMapping
{
    #[Identifier]
    private string $orderId;
    private string $status;

    private function __construct(string $orderId, string $status)
    {
        $this->orderId = $orderId;
        $this->status = $status;
    }

    #[EventHandler(identifierMapping: ['orderId' => 'payload.id'])]
    public static function createWhen(OrderStarted $event): self
    {
        return new self($event->id, $event->status);
    }

    #[EventHandler(identifierMapping: ['orderId' => 'payload.id'])]
    public function createWhenOnExisting(OrderStarted $event): void
    {
        $this->status = $event->status;
    }


    #[Asynchronous('async')]
    #[EventHandler(endpointId: 'createWhenAsync', identifierMapping: ['orderId' => 'payload.id'])]
    public static function createWhenAsync(OrderStartedAsynchronous $event): self
    {
        return new self($event->id, $event->status);
    }

    #[Asynchronous('async')]
    #[EventHandler(endpointId: 'createWhenAsync2', identifierMapping: ['orderId' => 'payload.id'])]
    public function createWhenAsyncOnExisting(OrderStartedAsynchronous $event): void
    {
        $this->status = $event->status;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
