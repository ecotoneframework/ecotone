<?php


namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate;

use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateFactory;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;

/**
 * Class NonStaticFactoryMethodExample
 * @package Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @Aggregate()
 */
class NonStaticFactoryMethodExample
{
    /**
     * @AggregateIdentifier()
     * @var string
     */
    private $id;

    /**
     * @CommandHandler()
     */
    public function doSomething() : void {}

    /**
     * @AggregateFactory()
     */
    public function factory(iterable $events){}
}