<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\Identifier;
use stdClass;

#[Aggregate]
class AggregateCommandHandlerWithReferencesExample
{
    #[Identifier]
    private string $id;

    #[CommandHandler('input', 'command-id-with-references')]
    public function doAction(DoStuffCommand $command, stdClass $injectedService): void
    {
    }
}
