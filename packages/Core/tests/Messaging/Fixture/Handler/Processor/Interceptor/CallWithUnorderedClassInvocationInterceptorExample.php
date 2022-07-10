<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\ClassReference;
use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

#[ClassReference("callWithUnordered")]
class CallWithUnorderedClassInvocationInterceptorExample extends BaseInterceptorExample
{
    #[Around]
    public function callWithUnorderedClassInvocation(MethodInvocation $methodInvocation, int $test, \stdClass $stdClass)
    {
        return $methodInvocation->proceed();
    }
}