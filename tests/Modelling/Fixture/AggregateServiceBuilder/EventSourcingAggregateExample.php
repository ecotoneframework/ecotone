<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Modelling\WithAggregateVersioning;
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

    #[Apply]
    public function applyAggregateCreated(AggregateCreatedInterface $event): void
    {
        $this->id = $event->id;
    }

    #[Apply]
    public function applySomethingWasDone(SomethingWasDone $event): void
    {
        $this->somethingWasDone = true;
    }
}
