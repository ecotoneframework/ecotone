<?php


namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;

#[EventSourcingAggregate]
class NoFactoryMethodAggregateExample
{
    #[AggregateIdentifier]
    private string $id;

    #[CommandHandler]
    public function doSomething(iterable $events) : void {}
}