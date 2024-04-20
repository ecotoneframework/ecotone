<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Identifier;

#[Aggregate]
final class Author
{
    private function __construct(
        #[Identifier] private string $id
    ) {

    }

    #[CommandHandler('create.author')]
    public static function create(string $id): self
    {
        return new self($id);
    }
}
