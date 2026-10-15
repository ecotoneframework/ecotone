<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Interceptor\MethodInvocation;

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
