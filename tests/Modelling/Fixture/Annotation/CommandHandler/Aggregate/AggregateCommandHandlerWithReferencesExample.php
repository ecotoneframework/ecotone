<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\CommandHandler;

#[Aggregate]
class AggregateCommandHandlerWithReferencesExample
{
    /**
     * @var string
     * @AggregateIdentifier()
     */
    private $id;

    #[CommandHandler("input", "command-id-with-references")]
    public function doAction(DoStuffCommand $command, \stdClass $injectedService) : void
    {

    }
}