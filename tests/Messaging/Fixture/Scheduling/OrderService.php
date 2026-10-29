<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Scheduling;

use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\EventBus;

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
