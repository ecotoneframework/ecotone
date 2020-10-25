<?php


namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\IncorrectEventTypeReturned;


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
class IncorrectEventTypeReturnedExample
{
    /**
     * @AggregateIdentifier()
     * @var string
     */
    private $id;

    #[CommandHandler]
    public static function create(CreateIncorrectEventTypeReturnedAggregate $command) : array
    {
        return [["id" => 1]];
    }

    /**
     * @AggregateFactory()
     */
    public static function factory(array $events) : self
    {
        $noIdDefinedAfterCallingFactoryExample = new self();
        $noIdDefinedAfterCallingFactoryExample->id = 1;

        return $noIdDefinedAfterCallingFactoryExample;
    }
}