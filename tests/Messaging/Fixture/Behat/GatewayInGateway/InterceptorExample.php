<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\GatewayInGateway;

use Ecotone\Messaging\Annotation\ClassReference;
use Ecotone\Messaging\Annotation\Interceptor\After;
use Ecotone\Messaging\Annotation\Interceptor\Around;
use Ecotone\Messaging\Annotation\Interceptor\Before;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

class InterceptorExample
{
    #[Before(pointcut: SomeQueryHandler::class . "::sum")]
    public function multiplyBefore(int $amount): int
    {
        return $amount * 2;
    }

    #[Around(pointcut: SomeQueryHandler::class . "::multiply")]
    public function sum(MethodInvocation $methodInvocation): int
    {
        $proceed = $methodInvocation->proceed();
        return $proceed + 1;
    }

    #[After(pointcut: SomeQueryHandler::class . "::sum")]
    public function multiplyAfter(int $amount): int
    {
        return $amount * 2;
    }
}