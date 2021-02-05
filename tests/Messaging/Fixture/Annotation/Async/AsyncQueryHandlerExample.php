<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

class AsyncQueryHandlerExample
{
    #[Asynchronous("asyncChannel")]
    #[QueryHandler(endpointId: "asyncEvent")]
    public function doSomething(\stdClass $event) : void
    {

    }
}