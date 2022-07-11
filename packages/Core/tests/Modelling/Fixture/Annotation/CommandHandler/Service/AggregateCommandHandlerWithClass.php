<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Modelling\Attribute\CommandHandler;
use stdClass;

class AggregateCommandHandlerWithClass
{
    #[CommandHandler(endpointId: 'commandHandler')]
    public function execute(stdClass $command): int
    {
        return 1;
    }
}
