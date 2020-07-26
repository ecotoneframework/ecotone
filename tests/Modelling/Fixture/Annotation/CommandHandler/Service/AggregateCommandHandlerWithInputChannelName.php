<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;

class AggregateCommandHandlerWithInputChannelName
{
    /**
     * @return int
     * @CommandHandler(inputChannelName="execute", endpointId="commandHandler")
     */
    public function execute() : int
    {
        return 1;
    }
}