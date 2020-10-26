<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Annotation\Interceptor\Around;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Message;

class CallWithPassThroughInterceptorExample extends BaseInterceptorExample
{
    #[Around]
    public function callWithPassThrough(Message $message) : void
    {

    }
}