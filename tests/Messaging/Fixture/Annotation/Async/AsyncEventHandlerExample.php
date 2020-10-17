<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\EventHandler;

#[Asynchronous("asyncChannel")]
class AsyncEventHandlerExample
{
    /**
     * @param \stdClass $event
     *
     * @EventHandler(endpointId="asyncEvent")
     */
    #[Asynchronous("asyncChannel")]
    public function doSomething(\stdClass $event) : void
    {

    }
}