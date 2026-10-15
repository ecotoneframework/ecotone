<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Scheduling;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Gateway\EventBus;

/**
 * licence Apache-2.0
 */
final class OrderService
{
    #[CommandHandler('order.register')]
    public function handle(PlaceOrder $command, EventBus $eventBus): void
    {
        $eventBus->publish(new OrderWasPlaced($command->orderId));
    }
}
