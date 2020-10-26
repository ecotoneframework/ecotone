<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\IgnorePayload;

#[Aggregate]
class ServiceCommandHandlerWithInputChannelNameAndIgnoreMessage
{
    #[CommandHandler("execute", "commandHandler")]
    #[IgnorePayload]
    public function execute(\stdClass $class) : int
    {
        return 1;
    }
}