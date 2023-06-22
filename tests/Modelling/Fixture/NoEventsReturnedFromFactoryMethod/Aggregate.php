<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\NoEventsReturnedFromFactoryMethod;

use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\WithAggregateVersioning;

#[EventSourcingAggregate]
final class Aggregate
{
    use WithAggregateVersioning;

    #[AggregateIdentifier]
    private int $id;

    #[CommandHandler(routingKey: 'aggregate.create')]
    public static function create(): array
    {
        return [];
    }

    #[EventSourcingHandler]
    public function when(AggregateCreated $event): void
    {
        $this->id = $event->id;
    }
}
