<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\CommandHandler;

#[Aggregate]
class ServiceCommandHandlerWithInputChannelNameAndObject
{
    #[CommandHandler("execute", "commandHandler")]
    public function execute(\stdClass $class) : int
    {
        return 1;
    }
}