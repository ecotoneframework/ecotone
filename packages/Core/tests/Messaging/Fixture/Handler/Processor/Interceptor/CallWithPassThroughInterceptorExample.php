<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Message;

class CallWithPassThroughInterceptorExample extends BaseInterceptorExample
{
    #[Around]
    public function callWithPassThrough(Message $message): void
    {
    }
}
