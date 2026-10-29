<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\PriorityEventHandler;

use Ecotone\Messaging\Attribute\Endpoint\Priority;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

/**
 * licence Apache-2.0
 */
final class SynchronousPriorityHandlerWithInheritance
{
    public array $triggers = [];

    #[Priority(3)]
    #[EventHandler(endpointId: 'middlePriorityHandler')]
    public function middlePriorityHandler(OrderWasPlaced $event): void
    {
        $this->triggers[] = 'middlePriorityHandler';
    }

    #[Priority(1)]
    #[EventHandler(endpointId: 'lowerPriorityHandlerWithObjectRouting')]
    public function lowerPriorityHandler(object $event): void
    {
        $this->triggers[] = 'lowerPriorityHandlerWithObjectRouting';
    }

    #[Priority(5)]
    #[EventHandler(endpointId: 'higherPriorityHandler')]
    public function higherPriorityHandler(OrderWasPlaced $event): void
    {
        $this->triggers[] = 'higherPriorityHandler';
    }

    #[QueryHandler('getTriggers')]
    public function getTriggers(): array
    {
        return $this->triggers;
    }
}
