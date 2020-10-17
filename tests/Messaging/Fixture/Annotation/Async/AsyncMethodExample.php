<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ServiceActivator;

class AsyncMethodExample
{
    /**
     * @ServiceActivator(endpointId="asyncServiceActivator", inputChannelName="inputChannel")
     */
    #[Asynchronous("asyncChannel")]
    public function doSomething() : void
    {

    }
}