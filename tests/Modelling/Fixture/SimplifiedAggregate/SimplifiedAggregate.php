<?php

namespace Test\Ecotone\Modelling\Fixture\SimplifiedAggregate;

use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class SimplifiedAggregate
{
    public function __construct(#[Identifier] private string $id, private bool $isEnabled = false)
    {
    }

    #[CommandHandler('aggregate.create')]
    public static function create(#[Reference] IdGenerator $idGenerator): static
    {
        return new self($idGenerator->generate());
    }

    #[CommandHandler('aggregate.enable')]
    public function enable(#[Reference] IdGenerator $idGenerator): void
    {
        $this->isEnabled = true;
    }

    public function getId(): string
    {
        return $this->id;
    }

    #[QueryHandler('aggregate.isEnabled')]
    public function isEnabled(#[Reference] IdGenerator $idGenerator): bool
    {
        return $this->isEnabled;
    }
}
