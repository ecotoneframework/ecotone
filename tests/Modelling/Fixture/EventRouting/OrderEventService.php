<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\EventRouting;

use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ecotone\Modelling\EventBus;

/**
 * licence Apache-2.0
 */
final class OrderEventService
{
    private array $handlersCalled = [];

    #[CommandHandler]
    public function handle(PlaceOrder $command, EventBus $eventBus): void
    {
        $eventBus->publish(new OrderWasPlaced($command->orderId));
    }

    #[EventHandler]
    public function whenOrderWasPlacedFirst(OrderWasPlaced $event): void
    {
        $this->handlersCalled[] = 'handler1';
    }

    #[EventHandler]
    public function whenOrderWasPlacedSecond(OrderWasPlaced $event): void
    {
        $this->handlersCalled[] = 'handler2';
    }

    #[QueryHandler('getHandlersCalled')]
    public function getHandlersCalled(): array
    {
        return $this->handlersCalled;
    }
}
