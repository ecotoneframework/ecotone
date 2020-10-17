<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ServiceActivator;

#[Asynchronous(channelName: "asyncChannel2")]
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
     */
    #[Asynchronous("asyncChannel1")]
    public function doSomething1() : void
    {

    }
}