<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class AggregateCommandHandlerWithDoubledFactoryMethod
{
    #[Identifier]
    private string $id;

    #[CommandHandler('sameChannel')]
    public static function factory(): void
    {
    }

    #[CommandHandler('sameChannel')]
    public static function factory2(): void
    {
    }
}
