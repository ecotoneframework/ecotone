<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Attribute\ConsoleCommand;
use Ecotone\Messaging\Config\ConsoleCommandResultSet;

class OneTimeWithResultExample
{
    #[ConsoleCommand("doSomething")]
    public function execute() : ConsoleCommandResultSet
    {

    }
}