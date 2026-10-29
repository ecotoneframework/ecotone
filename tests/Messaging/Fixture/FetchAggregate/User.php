<?php

namespace Test\Ecotone\Messaging\Fixture\FetchAggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\Identifier;

#[Aggregate]
/**
 * licence Enterprise
 */
class User
{
    public function __construct(
        #[Identifier] private string $userId,
        private string $name
    ) {
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function changeName(string $newName): void
    {
        $this->name = $newName;
    }
}
