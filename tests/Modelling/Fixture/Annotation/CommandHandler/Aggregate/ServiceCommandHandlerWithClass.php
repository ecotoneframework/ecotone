<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;

#[Aggregate]
class ServiceCommandHandlerWithClass
{
    #[CommandHandler(endpointId: "commandHandler")]
    public function execute(\stdClass $command) : int
    {
        return 1;
    }
}