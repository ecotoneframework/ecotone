<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\CommandHandler;

#[Aggregate]
class ServiceCommandHandlerWithClass
{
    #[CommandHandler(endpointId: "commandHandler")]
    public function execute(\stdClass $command) : int
    {
        return 1;
    }
}