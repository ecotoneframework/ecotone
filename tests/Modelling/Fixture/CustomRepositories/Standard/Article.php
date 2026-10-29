<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\CustomRepositories\Standard;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Identifier;

#[Aggregate]
/**
 * licence Apache-2.0
 */
final class Article
{
    private function __construct(
        #[Identifier] private string $id
    ) {

    }

    #[CommandHandler('create.article')]
    public static function create(string $id): self
    {
        return new self($id);
    }
}
