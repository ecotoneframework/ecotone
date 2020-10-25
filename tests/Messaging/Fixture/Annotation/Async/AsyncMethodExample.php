<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ServiceActivator;

class AsyncMethodExample
{
    #[Asynchronous("asyncChannel")]
    #[ServiceActivator("inputChannel", "asyncServiceActivator")]
    public function doSomething() : void
    {

    }
}