<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\PriorityEventHandler;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\Priority;
use Ecotone\Api\Modelling\WithEvents;

#[Aggregate]
/**
 * licence Apache-2.0
 */
final class AggregateSynchronousPriorityWithLowerPriorityHandler
{
    use WithEvents;

    #[Identifier]
    private int $id;

    private function __construct(int $id)
    {
        $this->id = $id;
        $this->recordThat(new OrderWasPlaced($id));
    }

    #[CommandHandler('setup')]
    public static function setup(int $identifier): self
    {
        return new self($identifier);
    }

    #[Priority(2)]
    #[EventHandler]
    public function lowerPriorityHandler(OrderWasPlaced $event, SynchronousPriorityHandler $priorityHandler): void
    {
        $priorityHandler->triggers[] = 'aggregateLowerPriorityHandler';
    }
}
