<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Annotation\ConsoleCommand;

class DefaultParametersOneTimeCommandExample
{
    #[ConsoleCommand("doSomething")]
    public function execute(string $name, string $surname = "cash") : void
    {

    }
}