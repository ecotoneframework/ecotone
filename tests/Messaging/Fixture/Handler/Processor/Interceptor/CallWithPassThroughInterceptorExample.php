<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Messaging\Message;

/**
 * licence Apache-2.0
 */
class CallWithPassThroughInterceptorExample extends BaseInterceptorExample
{
    #[Around]
    public function callWithPassThrough(Message $message): void
    {
    }
}
