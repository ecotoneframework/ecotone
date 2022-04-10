<?php

namespace Test\Ecotone\Modelling\Fixture\LateAggregateIdAssignationWithAggregateIdFromMethod;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\AggregateIdentifierMethod;
use Ecotone\Modelling\Attribute\CommandHandler;

#[Aggregate]
class User
{
    public $internalId;

    public $name;

    #[CommandHandler("user.create")]
    public static function create(CreateUser $command): self
    {
        $self = new self();
        $self->name = $command->name();

        return $self;
    }

    #[AggregateIdentifierMethod("id")]
    public function getIdentifier()
    {
        return $this->internalId;
    }
}