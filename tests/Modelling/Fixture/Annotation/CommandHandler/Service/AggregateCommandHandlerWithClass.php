<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Modelling\Attribute\CommandHandler;
use stdClass;

/**
 * licence Apache-2.0
 */
class AggregateCommandHandlerWithClass
{
    #[CommandHandler(endpointId: 'commandHandler')]
    public function execute(stdClass $command): int
    {
        return 1;
    }
}
