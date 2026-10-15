<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;

#[Aggregate]
/**
 * licence Apache-2.0
 */
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
