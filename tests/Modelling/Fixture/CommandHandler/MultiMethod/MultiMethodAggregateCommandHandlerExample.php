<?php

namespace Test\Ecotone\Modelling\Fixture\CommandHandler\MultiMethod;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\NotUniqueHandler;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class MultiMethodAggregateCommandHandlerExample
{
    #[Identifier]
    private string $id;

    #[CommandHandler('register', '1')]
    #[NotUniqueHandler]
    public function doAction1(array $data): void
    {
    }

    #[CommandHandler('register', '2')]
    #[NotUniqueHandler]
    public function doAction2(array $data): void
    {
    }
}
