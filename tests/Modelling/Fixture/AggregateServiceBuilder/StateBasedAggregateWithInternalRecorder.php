<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\WithEvents;

#[Aggregate]
final class StateBasedAggregateWithInternalRecorder
{
    use WithEvents;

    public function __construct(#[Identifier] public int $id)
    {
    }

    #[CommandHandler]
    public static function create(CreateAggregate $command): static
    {
        $self = new self($command->id);
        $self->recordThat(new AggregateCreated($command->id));

        return $self;
    }

    #[CommandHandler]
    public function doSomething(DoSomething $command): void
    {
        $this->recordThat(new SomethingWasDone($this->id));
    }
}
