<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Attribute\ConsoleCommand;
use stdClass;

class ParametersWithReferenceOneTimeCommandExample
{
    #[ConsoleCommand('doSomething')]
    public function execute(string $name, string $surname, stdClass $object): void
    {
    }
}
