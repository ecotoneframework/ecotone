<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;

#[Aggregate]
class AggregateCommandHandlerWithFactoryMethod
{
    #[AggregateIdentifier]
    private string $id;

    #[CommandHandler(endpointId: 'factory-id')]
    public static function doAction(DoStuffCommand $command): void
    {
    }
}
