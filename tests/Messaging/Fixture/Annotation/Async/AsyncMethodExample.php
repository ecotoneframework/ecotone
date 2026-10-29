<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Attribute\ServiceActivator;

/**
 * licence Apache-2.0
 */
class AsyncMethodExample
{
    #[Asynchronous('asyncChannel')]
    #[ServiceActivator('inputChannel', 'asyncServiceActivator')]
    public function doSomething(): void
    {
    }
}
