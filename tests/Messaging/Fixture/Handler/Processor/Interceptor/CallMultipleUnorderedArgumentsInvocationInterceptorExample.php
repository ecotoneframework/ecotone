<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Annotation\Interceptor\Around;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

/**
 * Class CallMultipleUnorderedArgumentsInvocationInterceptorExample
 * @package Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MethodInterceptor()
 */
class CallMultipleUnorderedArgumentsInvocationInterceptorExample extends BaseInterceptorExample
{
    /**
     * @param MethodInvocation $methodInvocation
     * @param int[]|iterable $numbers
     * @param string[]|array $strings
     * @param \stdClass $some
     * @return mixed
     * @Around()
     */
    public function callMultipleUnorderedArgumentsInvocation(MethodInvocation $methodInvocation, iterable $numbers, array $strings, \stdClass $some)
    {
        return $methodInvocation->proceed();
    }
}