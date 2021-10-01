<?php

namespace Test\Ecotone\Modelling\Fixture\SimplifiedAggregate;

use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Ramsey\Uuid\Uuid;

#[Aggregate]
class SimplifiedAggregate
{
    public function __construct(#[AggregateIdentifier] private string $id, private bool $isEnabled = false){}

    #[CommandHandler("aggregate.create")]
    public static function create(#[Reference] IdGenerator $idGenerator): static
    {
        return new self($idGenerator->generate());
    }

    #[CommandHandler("aggregate.enable")]
    public function enable(#[Reference] IdGenerator $idGenerator): void
    {
        $this->isEnabled = true;
    }

    public function getId(): string
    {
        return $this->id;
    }

    #[QueryHandler("aggregate.isEnabled")]
    public function isEnabled(#[Reference] IdGenerator $idGenerator): bool
    {
        return $this->isEnabled;
    }
}