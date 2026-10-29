<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Distributed\DistributedEventBus\ReceiverOrder;

use Ecotone\Messaging\Message;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

final class OrderServiceReceiver
{
    public const GET_ORDERS_COUNT      = 'getOrdersCount';
    public const GET_ORDERS      = 'getOrders';

    private array $orders = [];

    #[Distributed]
    #[EventHandler('userService.billing.DetailsWereChanged')]
    public function registerTicket(
        Message $message,
    ): void {
        $this->orders[] = $message;
    }

    #[QueryHandler(self::GET_ORDERS_COUNT)]
    public function getOrdersCount(): int
    {
        return count($this->orders);
    }

    #[QueryHandler(self::GET_ORDERS)]
    public function getOrders(): array
    {
        return $this->orders;
    }
}
