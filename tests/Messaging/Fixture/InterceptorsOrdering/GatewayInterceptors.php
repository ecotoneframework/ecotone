<?php

namespace Test\Ecotone\Messaging\Fixture\InterceptorsOrdering;

use Ecotone\Messaging\Attribute\Interceptor\After;
use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Interceptor\Before;
use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

/**
 * licence Apache-2.0
 */
class GatewayInterceptors
{
    #[Around(pointcut: Gateway::class)]
    public function around(MethodInvocation $methodInvocation, #[Reference] InterceptorOrderingStack $stack): mixed
    {
        $stack->add('gateway::around begin');
        $result = $methodInvocation->proceed();
        $stack->add('gateway::around end');
        return $result;
    }

    #[Before(pointcut: Gateway::class)]
    public function before(#[Reference] InterceptorOrderingStack $stack): void
    {
        $stack->add('gateway::before');
    }

    #[After(pointcut: Gateway::class)]
    public function after(#[Reference] InterceptorOrderingStack $stack): void
    {
        $stack->add('gateway::after');
    }
}
