<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand;

use Ecotone\Api\Attribute\ConsoleCommand;
use Ecotone\Api\Console\ConsoleCommandResultSet;

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
