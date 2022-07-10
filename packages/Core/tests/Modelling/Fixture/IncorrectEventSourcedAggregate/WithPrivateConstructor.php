<?php


namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateFactory;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;

#[EventSourcingAggregate]
class WithPrivateConstructor
{
    #[AggregateIdentifier]
    private string $id;

    private function __construct()
    {

    }

    #[CommandHandler]
    public function doSomething() : void {}

    #[EventSourcingHandler]
    public function factory(\stdClass $event){}
}