<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder;

use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\WithAggregateVersioning;
use stdClass;

#[EventSourcingAggregate]
/**
 * licence Apache-2.0
 */
final class EventSourcingAggregateExample
{
    use WithAggregateVersioning;

    #[Identifier] private int $id;

    private bool $somethingWasDone = false;

    #[CommandHandler]
    public static function create(CreateAggregate $command): array
    {
        return [new AggregateCreated($command->id)];
    }

    #[CommandHandler]
    public function doSomething(DoSomething $command): array
    {
        return [new SomethingWasDone($this->id)];
    }

    #[CommandHandler('aggregate.onlySideEffects')]
    public function handle(stdClass $class): void
    {
        $class->name = 'test';
    }

    #[EventSourcingHandler]
    public function applyAggregateCreated(AggregateCreatedInterface $event): void
    {
        $this->id = $event->id;
    }

    #[EventSourcingHandler]
    public function applySomethingWasDone(SomethingWasDone $event): void
    {
        $this->somethingWasDone = true;
    }
}
