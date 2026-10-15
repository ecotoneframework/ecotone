<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\EventRouting;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Gateway\EventBus;

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
