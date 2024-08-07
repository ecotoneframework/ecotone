<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Identifier;

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
