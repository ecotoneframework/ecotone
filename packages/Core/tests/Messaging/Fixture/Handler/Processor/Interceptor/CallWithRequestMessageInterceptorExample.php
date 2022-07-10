<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;

class CallWithRequestMessageInterceptorExample extends BaseInterceptorExample
{
    #[Around]
    public function callWithRequestMessage(MethodInvocation $methodInvocation, Message $message)
    {
        return $message;
    }
}