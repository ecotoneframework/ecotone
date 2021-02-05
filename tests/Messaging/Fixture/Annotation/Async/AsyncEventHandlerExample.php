<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Annotation\EventHandler;

#[Asynchronous("asyncChannel")]
class AsyncEventHandlerExample
{
    #[Asynchronous("asyncChannel")]
    #[EventHandler(endpointId: "asyncEvent")]
    public function doSomething(\stdClass $event) : void
    {

    }
}