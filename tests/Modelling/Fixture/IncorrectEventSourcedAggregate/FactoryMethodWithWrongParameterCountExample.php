<?php


namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate;

use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateFactory;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;

#[Aggregate]
class FactoryMethodWithWrongParameterCountExample
{
    /**
     * @AggregateIdentifier()
     * @var string
     */
    private $id;

    #[CommandHandler]
    public function doSomething() : void {}

    #[AggregateFactory]
    public static function factory(iterable $object, array $metadata){}
}