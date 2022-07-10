<?php

namespace Test\Ecotone\Modelling\Fixture\NoIdentifierAggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;

#[Aggregate]
class Product
{
    #[CommandHandler("create")]
    public static function create(array $payload): self
    {
        return new self();
    }
}