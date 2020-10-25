<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;

class CommandHandlerWithClassNameInAnnotation
{
    #[CommandHandler("input", "command-id")]
    public function execute() : int
    {
        return 1;
    }
}