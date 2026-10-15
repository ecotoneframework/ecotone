<?php

namespace Test\Ecotone\Modelling\Fixture\NoIdentifierAggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class Product
{
    #[CommandHandler('create')]
    public static function create(array $payload): self
    {
        return new self();
    }
}
