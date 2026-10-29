<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

/**
 * licence Apache-2.0
 */
class CallWithEndingChainAndReturningInterceptorExample extends BaseInterceptorExample
{
    #[Around]
    public function callWithEndingChainAndReturning(MethodInvocation $methodInvocation)
    {
        return $methodInvocation->proceed();
    }
}
