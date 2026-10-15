<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Interceptor\MethodInvocation;

/**
 * licence Apache-2.0
 */
class CallWithEndingChainNoReturningInterceptorExample extends BaseInterceptorExample
{
    #[Around]
    public function callWithEndingChainNoReturning(MethodInvocation $methodInvocation): void
    {
    }
}
