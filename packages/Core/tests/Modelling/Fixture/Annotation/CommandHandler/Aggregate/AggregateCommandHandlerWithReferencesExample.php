<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;

#[Aggregate]
class AggregateCommandHandlerWithReferencesExample
{
    #[AggregateIdentifier]
    private string $id;

    #[CommandHandler("input", "command-id-with-references")]
    public function doAction(DoStuffCommand $command, \stdClass $injectedService) : void
    {

    }
}