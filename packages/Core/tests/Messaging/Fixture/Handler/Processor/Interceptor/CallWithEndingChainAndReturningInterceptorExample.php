<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

class CallWithEndingChainAndReturningInterceptorExample extends BaseInterceptorExample
{
    #[Around]
    public function callWithEndingChainAndReturning(MethodInvocation $methodInvocation)
    {
        return $this->valueToReturn;
    }
}