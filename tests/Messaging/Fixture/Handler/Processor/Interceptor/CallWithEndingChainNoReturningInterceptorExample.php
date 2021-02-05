<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

class CallWithEndingChainNoReturningInterceptorExample extends BaseInterceptorExample
{
    #[Around]
    public function callWithEndingChainNoReturning(MethodInvocation $methodInvocation) : void
    {

    }
}