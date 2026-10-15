<?php

namespace Test\Ecotone\Modelling\Fixture\LateAggregateIdAssignationWithAggregateIdFromMethod;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\IdentifierMethod;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class User
{
    public $internalId;

    public $name;

    #[CommandHandler('user.create')]
    public static function create(CreateUser $command): self
    {
        $self = new self();
        $self->name = $command->name();

        return $self;
    }

    #[IdentifierMethod('id')]
    public function getIdentifier()
    {
        return $this->internalId;
    }
}
