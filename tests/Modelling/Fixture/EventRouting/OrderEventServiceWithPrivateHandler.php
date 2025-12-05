<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\EventRouting;

use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\EventBus;

/**
 * licence Apache-2.0
 */
final class OrderEventServiceWithPrivateHandler
{
    #[CommandHandler]
    public function handle(PlaceOrder $command, EventBus $eventBus): void
    {
        $eventBus->publish(new OrderWasPlaced($command->orderId));
    }

    #[EventHandler]
    public function whenOrderWasPlacedFirst(OrderWasPlaced $event): void
    {
    }

    #[EventHandler]
    private function whenOrderWasPlacedSecond(OrderWasPlaced $event): void
    {
    }
}
