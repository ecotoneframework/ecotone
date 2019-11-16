<?php


namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoIdDefinedAfterCallingFactory;


use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateFactory;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;

/**
 * Class NoIdDefinedAfterCallingFactoryExample
 * @package Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoIdDefinedAfterCallingFactory
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @Aggregate()
 */
class NoIdDefinedAfterCallingFactoryExample
{
    /**
     * @AggregateIdentifier()
     * @var string
     */
    private $id;

    /**
     * @CommandHandler()
     */
    public static function create(CreateNoIdDefinedAggregate $command) : array
    {
        return [new \stdClass()];
    }

    /**
     * @AggregateFactory()
     */
    public static function factory(array $events) : self
    {
        return new self();
    }
}