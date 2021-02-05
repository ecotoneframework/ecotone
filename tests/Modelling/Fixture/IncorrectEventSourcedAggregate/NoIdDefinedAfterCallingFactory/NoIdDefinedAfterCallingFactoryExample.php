<?php


namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoIdDefinedAfterCallingFactory;


use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateFactory;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\EventSourcedAggregate;

#[EventSourcedAggregate]
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