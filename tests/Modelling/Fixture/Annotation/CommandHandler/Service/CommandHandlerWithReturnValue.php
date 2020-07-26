<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;

class CommandHandlerWithReturnValue
{
    /**
     * @param SomeCommand $command
     *
     * @return int
     * @CommandHandler(inputChannelName="input", endpointId="command-id")
     */
    public function execute(SomeCommand $command, \stdClass $service1) : int
    {
        return 1;
    }
}