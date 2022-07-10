<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

class CallMultipleUnorderedArgumentsInvocationInterceptorExample extends BaseInterceptorExample
{
    /**
     * @param MethodInvocation $methodInvocation
     * @param int[]|iterable $numbers
     * @param string[]|array $strings
     * @param \stdClass $some
     * @return mixed
     */
    #[Around]
    public function callMultipleUnorderedArgumentsInvocation(MethodInvocation $methodInvocation, iterable $numbers, array $strings, \stdClass $some)
    {
        return $methodInvocation->proceed();
    }
}