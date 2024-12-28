<?php

namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifierMethod;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\WithEvents;
use Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate\NoIdDefinedAfterCallingFactory\CreateNoIdDefinedAggregate;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class PublicIdentifierGetMethodForEventSourcedAggregate
{
    use WithEvents;

    private $internalId;

    #[CommandHandler]
    public static function create(CreateNoIdDefinedAggregate $command): self
    {
        $self = new self();
        $self->internalId = $command->id;

        return $self;
    }

    #[AggregateIdentifierMethod('id')]
    public function getId()
    {
        return $this->internalId;
    }
}
