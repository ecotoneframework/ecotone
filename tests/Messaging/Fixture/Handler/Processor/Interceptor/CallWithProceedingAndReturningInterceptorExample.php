<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Interceptor\MethodInvocation;

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
