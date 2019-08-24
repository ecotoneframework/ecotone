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

    /**
     * @param array $command
     * @CommandHandler(inputChannelName="createAggregate")
     * @return AggregateWithoutMessageClassesExample
     */
    public static function createWithData(array $command) : self
    {
        $aggregateWithoutMessageClassesExample = new self();
        $aggregateWithoutMessageClassesExample->id =  $command['id'];

        return $aggregateWithoutMessageClassesExample;
    }

    /**
     * @return AggregateWithoutMessageClassesExample
     * @CommandHandler(inputChannelName="createAggregate")
     */
    public static function create() : self
    {
        $aggregateWithoutMessageClassesExample = new self();
        $aggregateWithoutMessageClassesExample->id =  1;

        return $aggregateWithoutMessageClassesExample;
    }

    /**
     * @CommandHandler(inputChannelName="doSomething")
     */
    public function doSomething() : void
    {
        $this->something = true;
    }

    /**
     * @CommandHandler(inputChannelName="doSomethingWithData")
     * @param array $data
     */
    public function doSomethingWithData(array $data) : void
    {
        $this->something = $data;
    }

    /**
     * @CommandHandler(inputChannelName="doSomething")
     * @param \stdClass $class
     */
    public function doSomethingWithReference(\stdClass $class) : void
    {
        $this->something = true;
    }

    /**
     * @QueryHandler(inputChannelName="querySomething")
     */
    public function querySomething()
    {
        return true;
    }

    /**
     * @QueryHandler(inputChannelName="querySomethingWithData")
     * @param array $data
     * @return array
     */
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