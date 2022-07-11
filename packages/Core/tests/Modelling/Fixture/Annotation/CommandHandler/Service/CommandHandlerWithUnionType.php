<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Modelling\Attribute\CommandHandler;
use stdClass;

class CommandHandlerWithUnionType
{
    #[CommandHandler]
    public function noAction(stdClass|HelloWorldCommand $command): void
    {
    }
}
