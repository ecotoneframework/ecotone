<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\NoEventsReturnedFromFactoryMethod;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
/**
 * licence Apache-2.0
 */
final class Aggregate
{
    use WithAggregateVersioning;

    #[Identifier]
    private int $id;

    #[CommandHandler(routingKey: 'aggregate.create')]
    public static function create(): array
    {
        return [];
    }

    #[Apply]
    public function when(AggregateCreated $event): void
    {
        $this->id = $event->id;
    }
}
