<?php


namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\IncorrectEventTypeReturned;


use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateFactory;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcedAggregate;

#[EventSourcedAggregate]
class IncorrectEventTypeReturnedExample
{
    #[AggregateIdentifier]
    private string $id;

    #[CommandHandler]
    public static function create(CreateIncorrectEventTypeReturnedAggregate $command) : array
    {
        return [["id" => 1]];
    }

    #[AggregateFactory]
    public static function factory(array $events) : self
    {
        $noIdDefinedAfterCallingFactoryExample = new self();
        $noIdDefinedAfterCallingFactoryExample->id = 1;

        return $noIdDefinedAfterCallingFactoryExample;
    }
}