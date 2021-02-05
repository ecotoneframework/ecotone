<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;

class CommandHandlerWithReturnValue
{
    #[CommandHandler("input", "command-id")]
    public function execute(SomeCommand $command, \stdClass $service1) : int
    {
        return 1;
    }
}