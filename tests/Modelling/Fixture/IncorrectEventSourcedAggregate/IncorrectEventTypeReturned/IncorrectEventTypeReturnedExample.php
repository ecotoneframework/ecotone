<?php


namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\IncorrectEventTypeReturned;


use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateFactory;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;

#[EventSourcingAggregate]
class IncorrectEventTypeReturnedExample
{
    #[AggregateIdentifier]
    private string $id;

    #[CommandHandler]
    public static function create(CreateIncorrectEventTypeReturnedAggregate $command) : array
    {
        return [["id" => 1]];
    }

    #[EventSourcingHandler]
    public static function factory(\stdClass $event) : void
    {
    }
}