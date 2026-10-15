<?php

namespace Test\Ecotone\Modelling\Fixture\SimplifiedAggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Reference;

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
    public function enable(): void
    {
        $this->isEnabled = true;
    }

    public function getId(): string
    {
        return $this->id;
    }

    #[QueryHandler('aggregate.isEnabled')]
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }
}
