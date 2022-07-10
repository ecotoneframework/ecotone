<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

class CallWithReplacingArgumentsInterceptorExample extends BaseInterceptorExample
{
    #[Around]
    public function callWithReplacingArguments(MethodInvocation $methodInvocation)
    {
        foreach ($this->argumentsToReplace as $parameterName => $value) {
            $methodInvocation->replaceArgument($parameterName, $value);
        }

        return $methodInvocation->proceed();
    }
}