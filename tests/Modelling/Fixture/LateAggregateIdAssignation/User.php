<?php

namespace Test\Ecotone\Modelling\Fixture\LateAggregateIdAssignation;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Identifier;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class User
{
    #[Identifier]
    public $id;

    public $name;

    #[CommandHandler('user.create')]
    public static function create(CreateUser $command): self
    {
        $self = new self();
        $self->name = $command->name();

        return $self;
    }
}
