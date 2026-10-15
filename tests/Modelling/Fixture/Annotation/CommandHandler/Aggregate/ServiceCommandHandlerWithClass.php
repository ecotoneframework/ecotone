<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\CommandHandler;
use stdClass;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class ServiceCommandHandlerWithClass
{
    #[CommandHandler(endpointId: 'commandHandler')]
    public function execute(stdClass $command): int
    {
        return 1;
    }
}
