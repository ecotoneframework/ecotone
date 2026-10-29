<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder;

use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventSourcingAggregate;
use Ecotone\Modelling\Attribute\EventSourcingHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\WithAggregateVersioning;
use Ecotone\Modelling\WithEvents;

#[EventSourcingAggregate(true)]
/**
 * licence Apache-2.0
 */
final class EventSourcingAggregateWithInternalRecorder
{
    use WithAggregateVersioning;
    use WithEvents;

    #[Identifier] private int $id;

    private bool $somethingWasDone = false;

    #[CommandHandler]
    public static function create(CreateAggregate $command): self
    {
        $self = new self();
        $self->recordThat(new AggregateCreated($command->id));

        return $self;
    }

    #[CommandHandler]
    public function doSomething(DoSomething $command): void
    {
        $this->recordThat(new SomethingWasDone($command->id));
    }

    #[CommandHandler]
    public function createSomething(CreateSomething $command): Something
    {
        $this->recordThat(new SomethingWasCreated($command->id, $command->somethingId));

        return new Something($command->somethingId);
    }

    #[EventSourcingHandler]
    public function applyAggregateCreated(AggregateCreated $event): void
    {
        $this->id = $event->id;
    }

    #[EventSourcingHandler]
    public function applySomethingWasDone(SomethingWasDone $event): void
    {
        $this->somethingWasDone = true;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getVersion(): int
    {
        return $this->version;
    }
}
