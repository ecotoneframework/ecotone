<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\HighThroughputPublishing;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\EventBus;

/**
 * licence Apache-2.0
 */
final class OrderService
{
    public function __construct(private OperationsLog $operationsLog)
    {
    }

    #[CommandHandler('order.place')]
    public function placeOrder(string $order, EventBus $eventBus): void
    {
        $this->operationsLog->log('command handler executed');
        $eventBus->publish(new OrderWasPlaced($order . '-1'));
        $eventBus->publish(new OrderWasPlaced($order . '-2'));
    }

    #[CommandHandler('order.forward')]
    public function forwardOrder(string $order, CommandBus $commandBus): void
    {
        $this->operationsLog->log('forwarding command handler executed');
        $commandBus->sendWithRouting('order.place', $order);
    }
}
