<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\CommandHandler;
use stdClass;

#[Asynchronous('asyncChannel')]
/**
 * licence Apache-2.0
 */
class AsyncCommandHandlerWithoutIdExample
{
    #[Asynchronous('asyncChannel')]
    #[CommandHandler]
    public function doSomething(stdClass $event): void
    {
    }
}
