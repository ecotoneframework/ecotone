<?php


namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Attribute\ServiceActivator;

class AsyncMethodExample
{
    #[Asynchronous("asyncChannel")]
    #[ServiceActivator("inputChannel", "asyncServiceActivator")]
    public function doSomething() : void
    {

    }
}