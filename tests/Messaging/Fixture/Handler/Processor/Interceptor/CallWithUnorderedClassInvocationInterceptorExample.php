<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor;

use SimplyCodedSoftware\Messaging\Annotation\Interceptor\Around;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

/**
 * Class CallWithUnorderedClassInvocationInterceptorExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MethodInterceptor()
 */
class CallWithUnorderedClassInvocationInterceptorExample extends BaseInterceptorExample
{
    /**
     * @param MethodInvocation $methodInvocation
     * @param int $test
     * @param \stdClass $stdClass
     * @return mixed
     * @Around()
     */
    public function callWithUnorderedClassInvocation(MethodInvocation $methodInvocation, int $test, \stdClass $stdClass)
    {
        return $methodInvocation->proceed();
    }
}