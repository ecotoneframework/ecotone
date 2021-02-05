<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\CommandHandler;

#[Aggregate]
class ServiceCommandHandlerWithInputChannelNameAndObject
{
    #[CommandHandler("execute", "commandHandler")]
    public function execute(\stdClass $class) : int
    {
        return 1;
    }
}