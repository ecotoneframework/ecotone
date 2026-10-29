<?php

namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate;

use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\Identifier;

#[EventSourcingAggregate]
/**
 * licence Apache-2.0
 */
class NoFactoryMethodAggregateExample
{
    #[Identifier]
    private string $id;

    #[CommandHandler]
    public function doSomething(iterable $events): void
    {
    }
}
