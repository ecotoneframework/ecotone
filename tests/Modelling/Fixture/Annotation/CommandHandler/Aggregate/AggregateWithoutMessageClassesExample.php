<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\Attribute\QueryHandler;
use stdClass;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class AggregateWithoutMessageClassesExample
{
    #[Identifier]
    private string $id;
    private $something;

    #[CommandHandler('createAggregate')]
    public static function createWithData(array $command): self
    {
        $aggregateWithoutMessageClassesExample = new self();
        $aggregateWithoutMessageClassesExample->id =  $command['id'];

        return $aggregateWithoutMessageClassesExample;
    }

    #[CommandHandler('createAggregateNoParams')]
    public static function create(int $id = 1): self
    {
        $aggregateWithoutMessageClassesExample = new self();
        $aggregateWithoutMessageClassesExample->id =  $id;

        return $aggregateWithoutMessageClassesExample;
    }

    #[CommandHandler('doSomething')]
    public function doSomething(): void
    {
        $this->something = true;
    }

    #[CommandHandler('doSomethingWithData')]
    public function doSomethingWithData(array $data): void
    {
        $this->something = $data;
    }

    #[CommandHandler('doSomethingWithReference')]
    public function doSomethingWithReference(stdClass $class): void
    {
        $this->something = true;
    }

    #[QueryHandler('querySomething')]
    public function querySomething()
    {
        return true;
    }

    #[QueryHandler('querySomethingWithData')]
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
