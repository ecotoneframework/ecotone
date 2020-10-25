<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;

class AggregateCommandHandlerWithInputChannelNameAndObject
{
    #[CommandHandler("execute", "commandHandler")]
    public function execute(\stdClass $class) : int
    {
        return 1;
    }
}