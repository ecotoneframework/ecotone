<?php

namespace Test\Ecotone\Modelling\Fixture\EventSourcedSaga;

use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\EventSourcingSaga;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingSaga]
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

    #[EventSourcingHandler]
    public function whenOrderStarted(OrderDispatchWasBegun $event): void
    {
        $this->orderId = $event->getOrderId();
        $this->status = 'new';
    }

    #[EventSourcingHandler]
    public function whenPaymentDone(OrderDispatchWasFinished $event): void
    {
        $this->status = 'closed';
    }
}
