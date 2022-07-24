<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\IgnorePayload;
use stdClass;

#[Aggregate]
class AggregateCommandHandlerWithNoCommandDataExample
{
    #[AggregateIdentifier]
    private string $id;

    #[CommandHandler('doActionChannel', 'command-id')]
    #[IgnorePayload]
    public function doAction(stdClass $class): void
    {
    }
}
