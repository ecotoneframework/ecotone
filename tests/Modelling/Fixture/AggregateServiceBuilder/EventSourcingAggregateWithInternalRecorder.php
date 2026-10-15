<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder;

use Ecotone\Api\Attribute\Apply;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventSourcingAggregate;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Modelling\WithAggregateVersioning;
use Ecotone\Api\Modelling\WithEvents;

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

    #[Apply]
    public function applyAggregateCreated(AggregateCreated $event): void
    {
        $this->id = $event->id;
    }

    #[Apply]
    public function applySomethingWasDone(SomethingWasDone $event): void
    {
        $this->somethingWasDone = true;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAggregateVersion(): int
    {
        return $this->version;
    }
}
