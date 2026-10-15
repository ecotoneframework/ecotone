<?php

namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;

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
