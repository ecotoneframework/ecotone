<?php

namespace Ecotone\Tests\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Attribute\ConsoleCommand;
use Ecotone\Messaging\Config\ConsoleCommandResultSet;

class OneTimeWithResultExample
{
    #[ConsoleCommand("doSomething")]
    public function execute() : ConsoleCommandResultSet
    {

    }
}