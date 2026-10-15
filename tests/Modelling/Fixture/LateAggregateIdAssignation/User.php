<?php

namespace Test\Ecotone\Modelling\Fixture\LateAggregateIdAssignation;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;

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
