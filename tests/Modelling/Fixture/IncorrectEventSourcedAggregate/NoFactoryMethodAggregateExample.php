<?php


namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate;

use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;

#[Aggregate]
class NoFactoryMethodAggregateExample
{
    /**
     * @AggregateIdentifier()
     * @var string
     */
    private $id;

    #[CommandHandler]
    public function doSomething(iterable $events) : void {}
}