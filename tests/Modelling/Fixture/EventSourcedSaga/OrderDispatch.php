<?php

namespace Test\Ecotone\Modelling\Fixture\EventSourcedSaga;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\EventSourcingSaga;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Modelling\WithAggregateVersioning;

#[EventSourcingSaga]
/**
 * licence Apache-2.0
 */
class OrderDispatch
{
    use WithAggregateVersioning;

    #[Identifier]
    private $orderId;
    /**
     * @var string
     */
    private $status;

    #[EventHandler]
    public static function createWith(OrderWasCreated $event): array
    {
        return [new OrderDispatchWasBegun($event->getOrderId())];
    }

    #[EventHandler]
    public function finishOrder(PaymentWasDoneEvent $event): array
    {
        return [new OrderDispatchWasFinished($event->getOrderId())];
    }

    public function getId(): string
    {
        return $this->orderId;
    }

    #[QueryHandler('order_dispatch.getStatus')]
    public function getStatus(): string
    {
        return $this->status;
    }

    #[Apply]
    public function whenOrderStarted(OrderDispatchWasBegun $event): void
    {
        $this->orderId = $event->getOrderId();
        $this->status = 'new';
    }

    #[Apply]
    public function whenPaymentDone(OrderDispatchWasFinished $event): void
    {
        $this->status = 'closed';
    }
}
