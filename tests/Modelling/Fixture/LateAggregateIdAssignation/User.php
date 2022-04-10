<?php

namespace Test\Ecotone\Modelling\Fixture\LateAggregateIdAssignation;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;

#[Aggregate]
class User
{
    #[AggregateIdentifier]
    public $id;

    public $name;

    #[CommandHandler("user.create")]
    public static function create(CreateUser $command): self
    {
        $self = new self();
        $self->name = $command->name();

        return $self;
    }
}