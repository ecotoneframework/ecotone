<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Messaging\Attribute\ConsoleCommand;
use Ecotone\Messaging\Config\ConsoleCommandResultSet;

/**
 * licence Apache-2.0
 */
class OneTimeWithResultExample
{
    #[ConsoleCommand('doSomething')]
    public function execute(): ConsoleCommandResultSet
    {
    }
}
