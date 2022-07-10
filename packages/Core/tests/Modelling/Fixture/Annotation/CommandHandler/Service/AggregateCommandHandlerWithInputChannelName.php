<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\CommandHandler;

class AggregateCommandHandlerWithInputChannelName
{
    #[CommandHandler("execute", "commandHandler")]
    public function execute() : int
    {
        return 1;
    }
}