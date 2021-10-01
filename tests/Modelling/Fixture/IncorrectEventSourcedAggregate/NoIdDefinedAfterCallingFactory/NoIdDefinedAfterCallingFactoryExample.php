<?php


namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoIdDefinedAfterCallingFactory;


use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateFactory;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;

#[EventSourcingAggregate]
class NoIdDefinedAfterCallingFactoryExample
{
    #[AggregateIdentifier]
    private $id;

    #[CommandHandler]
    public static function create(CreateNoIdDefinedAggregate $command) : array
    {
        return [new \stdClass()];
    }

    #[AggregateFactory]
    public static function factory(array $events) : self
    {
        return new self();
    }
}