<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\IgnorePayload;
use stdClass;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class AggregateCommandHandlerWithNoCommandDataExample
{
    #[Identifier]
    private string $id;

    #[CommandHandler('doActionChannel', 'command-id')]
    #[IgnorePayload]
    public function doAction(stdClass $class): void
    {
    }
}
