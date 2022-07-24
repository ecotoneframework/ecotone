<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;

#[Aggregate]
class AggregateCommandHandlerExample
{
    #[AggregateIdentifier]
    private string $id;

    #[CommandHandler(endpointId: 'command-id')]
    public function doAction(DoStuffCommand $command): void
    {
    }
}
