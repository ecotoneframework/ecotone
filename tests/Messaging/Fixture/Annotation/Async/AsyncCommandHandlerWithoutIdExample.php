<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\EventHandler;

/**
 * Class AsyncEventHandlerExample
 * @package Test\Ecotone\Messaging\Fixture\Annotation\Async
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 * @Asynchronous(channelName="asyncChannel")
 */
class AsyncCommandHandlerWithoutIdExample
{
    /**
     * @param \stdClass $event
     *
     * @Asynchronous(channelName="asyncChannel")
     * @CommandHandler()
     */
    public function doSomething(\stdClass $event) : void
    {

    }
}