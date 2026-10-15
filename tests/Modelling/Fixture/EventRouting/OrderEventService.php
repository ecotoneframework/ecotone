<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\EventRouting;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Gateway\EventBus;

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
