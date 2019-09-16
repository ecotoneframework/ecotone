<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Annotation\Async;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ServiceActivator;

/**
 * Class AsyncMethodExample
 * @package Test\Ecotone\Messaging\Fixture\Annotation\Async
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 */
class AsyncMethodExample
{
    /**
     * @ServiceActivator(endpointId="asyncServiceActivator", inputChannelName="inputChannel")
     * @Async(channelName="asyncChannel")
     */
    public function doSomething() : void
    {

    }
}