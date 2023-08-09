<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Identifier;

#[Aggregate]
class AggregateCommandHandlerWithRedirectionByClass
{
    #[Identifier]
    private string $id;

    #[CommandHandler(endpointId: 'factory')]
    public static function factory(DoStuffCommand $command): void
    {
    }

    #[CommandHandler(endpointId: 'action')]
    public function action(DoStuffCommand $command): void
    {
    }
}
