<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;

class CommandHandlerWithUnionType
{
    #[CommandHandler]
    public function noAction(\stdClass|HelloWorldCommand $command) : void
    {

    }
}