<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Aggregate]
class AggregateWithoutMessageClassesExample
{
    #[AggregateIdentifier]
    private string $id;
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