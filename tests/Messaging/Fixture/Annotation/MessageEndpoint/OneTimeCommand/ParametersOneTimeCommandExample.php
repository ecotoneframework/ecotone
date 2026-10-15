<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Api\Attribute\ConsoleCommand;

/**
 * licence Apache-2.0
 */
class ParametersOneTimeCommandExample
{
    #[ConsoleCommand('doSomething')]
    public function execute(string $name, string $surname): void
    {
    }
}
