<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute;

use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

/**
 * licence Apache-2.0
 */
final class OrderService
{
    private array $orders = [];

    #[Locking(static function (): string {
        return 'order-lock';
    })]
    #[CommandHandler('order.place')]
    public function placeOrder(string $order): void
    {
        $this->orders[] = $order;
    }

    #[QueryHandler('order.getOrders')]
    public function getOrders(): array
    {
        return $this->orders;
    }
}
