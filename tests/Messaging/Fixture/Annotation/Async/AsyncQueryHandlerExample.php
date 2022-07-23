<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\QueryHandler;
use stdClass;

class AsyncQueryHandlerExample
{
    #[Asynchronous('asyncChannel')]
    #[QueryHandler(endpointId: 'asyncEvent')]
    public function doSomething(stdClass $event): void
    {
    }
}
