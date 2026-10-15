<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class AggregateCommandHandlerExample
{
    #[Identifier]
    private string $id;

    #[CommandHandler(endpointId: 'command-id')]
    public function doAction(DoStuffCommand $command): void
    {
    }
}
