<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;

#[Aggregate]
/**
 * licence Apache-2.0
 */
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
