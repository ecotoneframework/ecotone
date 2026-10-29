<?php

/*
 * licence Enterprise
 */
declare(strict_types=1);

namespace EventSourcing\Fixture\Projecting;

use Ecotone\EventSourcing\Attribute\Projection;
use Ecotone\EventSourcing\Attribute\ProjectionDelete;
use Ecotone\EventSourcing\Attribute\ProjectionInitialization;
use Ecotone\EventSourcing\Attribute\ProjectionReset;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Projection('order_projection')]
class OrderProjection
{
    private bool $initialized = false;
    private array $orders = [];

    #[ProjectionInitialization]
    public function init(): void
    {
        $this->initialized = true;
    }

    #[ProjectionDelete]
    public function delete(): void
    {
        $this->initialized = false;
        $this->orders = [];
    }

    #[ProjectionReset]
    public function reset(): void
    {
        $this->orders = [];
    }

    #[QueryHandler]
    public function getOrders(): array
    {
        return $this->orders;
    }

    #[EventHandler]
    public function onOrderCreated(OrderCreated $order): void
    {
        $this->orders[$order->orderId] = $order;
    }

    #[EventHandler]
    public function onOrderCanceled(OrderCanceled $order): void
    {
        unset($this->orders[$order->orderId]);
    }
}
