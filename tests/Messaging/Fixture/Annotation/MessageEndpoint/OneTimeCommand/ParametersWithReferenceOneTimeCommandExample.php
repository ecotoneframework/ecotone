<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Api\Attribute\ConsoleCommand;
use stdClass;

/**
 * licence Apache-2.0
 */
class ParametersWithReferenceOneTimeCommandExample
{
    #[ConsoleCommand('doSomething')]
    public function execute(string $name, string $surname, stdClass $object): void
    {
    }
}
