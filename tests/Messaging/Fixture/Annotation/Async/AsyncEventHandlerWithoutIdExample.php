<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\EventHandler;
use stdClass;

#[Asynchronous('asyncChannel')]
/**
 * licence Apache-2.0
 */
class AsyncEventHandlerWithoutIdExample
{
    #[Asynchronous('asyncChannel')]
    #[EventHandler]
    public function doSomething(stdClass $event): void
    {
    }
}
