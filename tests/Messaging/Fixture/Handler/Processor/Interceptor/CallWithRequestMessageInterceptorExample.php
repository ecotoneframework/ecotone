<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Messaging\Message;

/**
 * licence Apache-2.0
 */
class CallWithRequestMessageInterceptorExample extends BaseInterceptorExample
{
    #[Around]
    public function callWithRequestMessage(MethodInvocation $methodInvocation, Message $message)
    {
        return $message;
    }
}
