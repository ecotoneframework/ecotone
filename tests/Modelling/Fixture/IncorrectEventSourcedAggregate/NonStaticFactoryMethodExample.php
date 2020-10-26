<?php


namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate;

use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateFactory;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;

#[Aggregate]
class NonStaticFactoryMethodExample
{
    /**
     * @AggregateIdentifier()
     * @var string
     */
    private $id;

    #[CommandHandler]
    public function doSomething() : void {}

    /**
     * @AggregateFactory()
     */
    public function factory(iterable $events){}
}