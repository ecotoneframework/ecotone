<?php

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

class PostSendInterceptor
{
    /**
     * @param array<ConsumerInterceptor> $interceptors
     */
    public function __construct(private array $interceptors)
    {
    }

    public function postSend(MethodInvocation $methodInvocation): mixed
    {
        foreach ($this->interceptors as $interceptor) {
            $interceptor->postSend();
        }
        return $methodInvocation->proceed();
    }
}
