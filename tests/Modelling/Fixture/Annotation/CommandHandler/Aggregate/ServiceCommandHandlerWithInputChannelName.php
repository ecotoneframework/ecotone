<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\CommandHandler;

#[Aggregate]
class ServiceCommandHandlerWithInputChannelName
{
    #[CommandHandler("execute", "commandHandler")]
    public function execute() : int
    {
        return 1;
    }
}