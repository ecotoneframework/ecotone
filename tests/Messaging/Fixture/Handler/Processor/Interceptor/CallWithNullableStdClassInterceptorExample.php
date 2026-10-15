<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Interceptor\MethodInvocation;
use stdClass;

/**
 * licence Apache-2.0
 */
class CallWithNullableStdClassInterceptorExample extends BaseInterceptorExample
{
    #[Around]
    public function callWithNullableStdClass(MethodInvocation $methodInvocation, ?stdClass $stdClass)
    {
        return $stdClass;
    }
}
