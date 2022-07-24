<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use stdClass;

class CallWithNullableStdClassInterceptorExample extends BaseInterceptorExample
{
    #[Around]
    public function callWithNullableStdClass(MethodInvocation $methodInvocation, ?stdClass $stdClass)
    {
        return $stdClass;
    }
}
