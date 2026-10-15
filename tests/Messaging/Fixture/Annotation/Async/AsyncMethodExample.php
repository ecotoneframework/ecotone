<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\InternalHandler;

/**
 * licence Apache-2.0
 */
class AsyncMethodExample
{
    #[Asynchronous('asyncChannel')]
    #[InternalHandler('inputChannel', endpointId: 'asyncServiceActivator')]
    public function doSomething(): void
    {
    }
}
