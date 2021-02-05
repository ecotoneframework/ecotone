<?php


namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate;

use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateFactory;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\EventSourcedAggregate;

#[EventSourcedAggregate]
class FactoryMethodWithWrongParameterCountExample
{
    #[AggregateIdentifier]
    private string $id;

    #[CommandHandler]
    public function doSomething() : void {}

    #[AggregateFactory]
    public static function factory(iterable $object, array $metadata){}
}