<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\EventHandler;

#[Asynchronous("asyncChannel")]
class AsyncEventHandlerWithoutIdExample
{
    #[Asynchronous("asyncChannel")]
    #[EventHandler]
    public function doSomething(\stdClass $event) : void
    {

    }
}