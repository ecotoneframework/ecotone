<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Async;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\InternalHandler;

#[Asynchronous(channelName: 'asyncChannel2')]
/**
 * licence Apache-2.0
 */
class AsyncClassExample
{
    #[InternalHandler('inputChannel', endpointId: 'asyncServiceActivator2')]
    public function doSomething2(): void
    {
    }

    #[Asynchronous('asyncChannel1')]
    #[InternalHandler('inputChannel', endpointId: 'asyncServiceActivator1')]
    public function doSomething1(): void
    {
    }
}
