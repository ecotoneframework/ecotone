<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\EventHandler;

/**
 * Class AsyncEventHandlerExample
 * @package Test\Ecotone\Messaging\Fixture\Annotation\Async
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 * @Asynchronous(channelName="asyncChannel")
 */
class AsyncEventHandlerWithoutIdExample
{
    /**
     * @param \stdClass $event
     *
     * @EventHandler()
     * @Asynchronous(channelName="asyncChannel")
     */
    public function doSomething(\stdClass $event) : void
    {

    }
}