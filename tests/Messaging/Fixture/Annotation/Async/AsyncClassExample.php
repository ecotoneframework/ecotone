<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ServiceActivator;

/**
 * Class AsyncMethodExample
 * @package Test\Ecotone\Messaging\Fixture\Annotation\Async
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MessageEndpoint()
 * @Asynchronous(channelName="asyncChannel2")
 */
class AsyncClassExample
{
    /**
     * @ServiceActivator(endpointId="asyncServiceActivator2", inputChannelName="inputChannel")
     */
    public function doSomething2() : void
    {

    }

    /**
     * @ServiceActivator(endpointId="asyncServiceActivator1", inputChannelName="inputChannel")
     * @Asynchronous(channelName="asyncChannel1")
     */
    public function doSomething1() : void
    {

    }
}