<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

/**
 * Class AggregateWithoutMessageClassesExample
 * @package Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Aggregate()
 */
class AggregateWithoutMessageClassesExample
{
    /**
     * @var string
     * @AggregateIdentifier()
     */
    private $id;
    private $something;

    #[CommandHandler("createAggregate")]
    public static function createWithData(array $command) : self
    {
        $aggregateWithoutMessageClassesExample = new self();
        $aggregateWithoutMessageClassesExample->id =  $command['id'];

        return $aggregateWithoutMessageClassesExample;
    }

    #[CommandHandler("createAggregate")]
    public static function create() : self
    {
        $aggregateWithoutMessageClassesExample = new self();
        $aggregateWithoutMessageClassesExample->id =  1;

        return $aggregateWithoutMessageClassesExample;
    }

    #[CommandHandler("doSomething")]
    public function doSomething() : void
    {
        $this->something = true;
    }

    #[CommandHandler("doSomethingWithData")]
    public function doSomethingWithData(array $data) : void
    {
        $this->something = $data;
    }

    #[CommandHandler("doSomething")]
    public function doSomethingWithReference(\stdClass $class) : void
    {
        $this->something = true;
    }

    #[QueryHandler("querySomething")]
    public function querySomething()
    {
        return true;
    }

    #[QueryHandler("querySomethingWithData")]
    public function querySomethingWithData(array $data)
    {
        return $data;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    public function getChangedState()
    {
        return $this->something;
    }
}