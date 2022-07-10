<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\CommandHandler;

class CommandHandlerWithClassNameInAnnotation
{
    #[CommandHandler("input", "command-id")]
    public function execute() : int
    {
        return 1;
    }
}