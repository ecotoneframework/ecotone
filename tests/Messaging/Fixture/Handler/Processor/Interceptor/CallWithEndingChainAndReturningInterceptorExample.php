<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Interceptor\MethodInvocation;

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
