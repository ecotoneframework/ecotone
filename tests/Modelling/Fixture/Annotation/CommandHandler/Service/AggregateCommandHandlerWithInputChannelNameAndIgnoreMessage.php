<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\IgnorePayload;

class AggregateCommandHandlerWithInputChannelNameAndIgnoreMessage
{
    #[CommandHandler("execute", "commandHandler")]
    #[IgnorePayload]
    public function execute(\stdClass $class) : int
    {
        return 1;
    }
}