<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Annotation\Interceptor\Around;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

class CallWithProceedingAndReturningInterceptorExample extends BaseInterceptorExample
{
    /**
     * @param MethodInvocation $methodInvocation
     * @return mixed
     * @Around()
     */
    public function callWithProceedingAndReturning(MethodInvocation $methodInvocation)
    {
        $this->wasCalled = true;

        return $methodInvocation->proceed();
    }
}