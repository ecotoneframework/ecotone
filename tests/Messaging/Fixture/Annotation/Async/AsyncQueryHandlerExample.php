<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

/**
 * Class AsyncEventHandlerExample
 * @package Test\Ecotone\Messaging\Fixture\Annotation\Async
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class AsyncQueryHandlerExample
{
    /**
     * @param \stdClass $event
     *
     * @QueryHandler(endpointId="asyncEvent")
     * @Asynchronous(channelName="asyncChannel")
     */
    public function doSomething(\stdClass $event) : void
    {

    }
}