<?php

namespace Test\Ecotone\Modelling\Fixture\IncorrectEventSourcedAggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\IdentifierMethod;
use Ecotone\Api\Modelling\WithEvents;
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

    #[IdentifierMethod('id')]
    public function getId()
    {
        return $this->internalId;
    }
}
