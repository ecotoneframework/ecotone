<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;

class AggregateCommandHandlerWithClass
{
    /**
     * @param \stdClass $command
     * @return int
     * @CommandHandler(endpointId="commandHandler")
     */
    public function execute(\stdClass $command) : int
    {
        return 1;
    }
}