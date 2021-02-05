<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\CommandHandler;

class AggregateCommandHandlerWithClass
{
    #[CommandHandler(endpointId: "commandHandler")]
    public function execute(\stdClass $command) : int
    {
        return 1;
    }
}