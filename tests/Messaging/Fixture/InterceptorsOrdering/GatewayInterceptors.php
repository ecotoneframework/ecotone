<?php

namespace Test\Ecotone\Messaging\Fixture\InterceptorsOrdering;

use Ecotone\Api\Attribute\After;
use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\Before;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\Interceptor\MethodInvocation;

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
