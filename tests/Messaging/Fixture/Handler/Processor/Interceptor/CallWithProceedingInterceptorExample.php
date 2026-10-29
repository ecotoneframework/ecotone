<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

/**
 * licence Apache-2.0
 */
class CallWithProceedingInterceptorExample extends BaseInterceptorExample
{
    #[Around]
    public function callWithProceeding(MethodInvocation $methodInvocation): void
    {
        $methodInvocation->proceed();
        $this->markAsCalled();
    }
}
