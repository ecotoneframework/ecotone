<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Attribute\ConsoleCommand;

/**
 * licence Apache-2.0
 */
class OneTimeWithIncorrectResultSet
{
    #[ConsoleCommand('doSomething')]
    public function execute(): array
    {
    }
}
