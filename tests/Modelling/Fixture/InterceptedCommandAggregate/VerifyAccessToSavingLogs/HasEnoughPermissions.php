<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\VerifyAccessToSavingLogs;

use Ecotone\Messaging\Annotation\Interceptor\Around;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\Logger;

/**
 * @MethodInterceptor()
 */
class HasEnoughPermissions
{
    /**
     * @Around(
     *     pointcut="@(Test\Ecotone\Modelling\Fixture\InterceptedCommandAggregate\VerifyAccessToSavingLogs\ValidateExecutor)"
     * )
     */
    public function validate(MethodInvocation $methodInvocation, Logger $logger)
    {
        $data = $methodInvocation->getArguments()[0];

        if (!$logger->hasAccess($data["executorId"])) {
            throw new \InvalidArgumentException("Not enough permissions");
        }

        return $methodInvocation->proceed();
    }
}