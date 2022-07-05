<?php

namespace Ecotone\Tests\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Attribute\ConsoleCommand;

class NoParameterOneTimeCommandExample
{
    #[ConsoleCommand("doSomething")]
    public function execute() : void
    {

    }
}