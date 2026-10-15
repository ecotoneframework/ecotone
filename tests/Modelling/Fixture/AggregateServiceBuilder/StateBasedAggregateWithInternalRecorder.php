<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\AggregateServiceBuilder;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Modelling\WithEvents;

#[Aggregate]
/**
 * licence Apache-2.0
 */
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
