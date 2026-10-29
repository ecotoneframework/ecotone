<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

/**
 * licence Apache-2.0
 */
class CallWithProceedingAndReturningInterceptorExample extends BaseInterceptorExample
{
    #[Around]
    public function callWithProceedingAndReturning(MethodInvocation $methodInvocation)
    {
        $this->wasCalled = true;

        return $methodInvocation->proceed();
    }
}
