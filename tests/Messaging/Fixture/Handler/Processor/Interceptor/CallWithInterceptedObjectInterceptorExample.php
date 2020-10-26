<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Annotation\Interceptor\Around;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\StubCallSavingService;

class CallWithInterceptedObjectInterceptorExample extends BaseInterceptorExample
{
    #[Around]
    public function callWithInterceptedObject(StubCallSavingService $stubCallSavingService): void
    {
    }
}