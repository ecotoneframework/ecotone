<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\PriorityEventHandler;

use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Priority;
use Ecotone\Api\Attribute\QueryHandler;

/**
 * licence Apache-2.0
 */
final class SynchronousPriorityHandler
{
    public array $triggers = [];

    #[Priority(3)]
    #[EventHandler(endpointId: 'middlePriorityHandler')]
    public function middlePriorityHandler(OrderWasPlaced $event): void
    {
        $this->triggers[] = 'middlePriorityHandler';
    }

    #[Priority(1)]
    #[EventHandler(endpointId: 'lowerPriorityHandler')]
    public function lowerPriorityHandler(OrderWasPlaced $event): void
    {
        $this->triggers[] = 'lowerPriorityHandler';
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
