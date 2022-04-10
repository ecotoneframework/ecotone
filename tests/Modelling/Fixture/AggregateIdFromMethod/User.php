<?php

namespace Test\Ecotone\Modelling\Fixture\AggregateIdFromMethod;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\AggregateIdentifierMethod;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Aggregate]
class User
{
    private string $internalId;
    private string $name;

    #[CommandHandler]
    public static function create(CreateUser $command): self
    {
        $self = new self();
        $self->internalId = $command->id;
        $self->name = $command->name;

        return $self;
    }

    #[AggregateIdentifierMethod("id")]
    public function getIdentifier()
    {
        return $this->internalId;
    }

    #[QueryHandler("user.getName")]
    public function getName(): string
    {
        return $this->name;
    }
}