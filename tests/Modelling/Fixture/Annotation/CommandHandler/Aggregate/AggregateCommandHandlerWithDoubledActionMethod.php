<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class AggregateCommandHandlerWithDoubledActionMethod
{
    #[Identifier]
    private string $id;

    #[CommandHandler('sameChannel')]
    public function action1(): void
    {
    }

    #[CommandHandler('sameChannel')]
    public function action2(): void
    {
    }
}
