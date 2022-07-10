<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Attribute\ConsoleCommand;

class NoParameterOneTimeCommandExample
{
    #[ConsoleCommand("doSomething")]
    public function execute() : void
    {

    }
}