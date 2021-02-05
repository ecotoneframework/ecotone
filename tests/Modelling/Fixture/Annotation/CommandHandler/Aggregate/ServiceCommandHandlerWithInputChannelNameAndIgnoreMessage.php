<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\IgnorePayload;

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