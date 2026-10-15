<?php

namespace Test\Ecotone\Messaging\Fixture\InterceptorsOrdering;

use Ecotone\Api\Attribute\After;
use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\Before;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Headers;
use Ecotone\Api\Attribute\Reference;
use Ecotone\Api\Interceptor\MethodInvocation;

/**
 * licence Apache-2.0
 */
class InterceptorOrderingInterceptors
{
    #[After(precedence: -1, pointcut: InterceptorOrderingAggregate::class . '||' . InterceptorOrderingCase::class, changeHeaders: true)]
    public function afterChangeHeaders(#[Headers] array $metadata, #[Reference] InterceptorOrderingStack $stack): array
    {
        $stack->add('afterChangeHeaders');
        return array_merge($metadata, ['afterChangeHeaders' => 'header']);
    }

    #[After(pointcut: InterceptorOrderingAggregate::class . '||' . InterceptorOrderingCase::class)]
    public function after(#[Reference] InterceptorOrderingStack $stack): void
    {
        $stack->add('after');
    }

    #[Before(precedence: -1, pointcut: InterceptorOrderingAggregate::class . '||' . InterceptorOrderingCase::class, changeHeaders: true)]
    public function beforeChangeHeaders(#[Headers] array $metadata, #[Reference] InterceptorOrderingStack $stack): array
    {
        $stack->add('beforeChangeHeaders');
        return array_merge($metadata, ['beforeChangeHeaders' => 'header']);
    }

    #[Before(pointcut: InterceptorOrderingAggregate::class . '||' . InterceptorOrderingCase::class)]
    public function before(#[Reference] InterceptorOrderingStack $stack): void
    {
        $stack->add('before');
    }

    #[Around(pointcut: InterceptorOrderingAggregate::class . '||' . InterceptorOrderingCase::class)]
    public function around(MethodInvocation $methodInvocation, #[Reference] InterceptorOrderingStack $stack): mixed
    {
        $stack->add('around begin');
        $result = $methodInvocation->proceed();
        $stack->add('around end');
        return $result;
    }

    #[EventHandler]
    public function eventHandler(CreatedEvent $event, #[Reference] InterceptorOrderingStack $stack): void
    {
        $stack->add('eventHandler');
    }
}
