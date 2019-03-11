<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor;

use SimplyCodedSoftware\Messaging\Annotation\Interceptor\Around;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

/**
 * Class CallMultipleUnorderedArgumentsInvocationInterceptorExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor
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