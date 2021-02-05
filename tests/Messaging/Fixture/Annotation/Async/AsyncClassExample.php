<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Attribute\ServiceActivator;

#[Asynchronous(channelName: "asyncChannel2")]
class AsyncClassExample
{
    #[ServiceActivator("inputChannel", "asyncServiceActivator2")]
    public function doSomething2() : void
    {

    }

    #[Asynchronous("asyncChannel1")]
    #[ServiceActivator("inputChannel", "asyncServiceActivator1")]
    public function doSomething1() : void
    {

    }
}