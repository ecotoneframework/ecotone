<?php

namespace Ecotone\Tests\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Attribute\ConsoleCommand;

class ParametersOneTimeCommandExample
{
    #[ConsoleCommand("doSomething")]
    public function execute(string $name, string $surname) : void
    {

    }
}