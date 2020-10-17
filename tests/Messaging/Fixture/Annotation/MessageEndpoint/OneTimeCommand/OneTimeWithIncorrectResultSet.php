<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Annotation\ConsoleCommand;
use Ecotone\Messaging\Config\OneTimeCommandResultSet;

class OneTimeWithIncorrectResultSet
{
    #[ConsoleCommand("doSomething")]
    public function execute() : array
    {

    }
}