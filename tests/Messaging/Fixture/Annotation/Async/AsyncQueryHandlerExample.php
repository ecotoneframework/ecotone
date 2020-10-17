<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

class AsyncQueryHandlerExample
{
    /**
     * @param \stdClass $event
     *
     * @QueryHandler(endpointId="asyncEvent")
     */
    #[Asynchronous("asyncChannel")]
    public function doSomething(\stdClass $event) : void
    {

    }
}