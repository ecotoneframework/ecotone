<?php

declare(strict_types=1);

namespace Test\Ecotone\Dbal\Fixture\DocumentStoreAggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Aggregate]
class Person
{
    #[AggregateIdentifier]
    private int $personId;
    private string $name;

    private function __construct(int $personId, string $name)
    {
        $this->personId = $personId;
        $this->name = $name;
    }

    #[CommandHandler]
    public static function register(RegisterPerson $command): static
    {
        return new self($command->getPersonId(), $command->getName());
    }

    #[QueryHandler('person.getName')]
    public function getName(): string
    {
        return $this->name;
    }

    public function getPersonId(): int
    {
        return $this->personId;
    }
}
