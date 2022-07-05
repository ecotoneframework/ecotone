<?php

namespace Ecotone\Tests\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Attribute\ConsoleCommand;

class DefaultParametersOneTimeCommandExample
{
    #[ConsoleCommand("doSomething")]
    public function execute(string $name, string $surname = "cash") : void
    {

    }
}