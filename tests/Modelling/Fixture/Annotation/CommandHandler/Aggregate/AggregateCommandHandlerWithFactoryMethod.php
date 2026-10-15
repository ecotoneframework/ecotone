<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class AggregateCommandHandlerWithFactoryMethod
{
    #[Identifier]
    private string $id;

    #[CommandHandler(endpointId: 'factory-id')]
    public static function doAction(DoStuffCommand $command): void
    {
    }
}
