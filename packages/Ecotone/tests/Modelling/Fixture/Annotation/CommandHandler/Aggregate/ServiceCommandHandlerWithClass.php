<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use stdClass;

#[Aggregate]
class ServiceCommandHandlerWithClass
{
    #[CommandHandler(endpointId: 'commandHandler')]
    public function execute(stdClass $command): int
    {
        return 1;
    }
}
