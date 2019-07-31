<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Annotation\Interceptor\Around;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Test\Ecotone\Messaging\Fixture\Handler\Processor\StubCallSavingService;

/**
 * Class CallWithInterceptedObjectInterceptorExample
 * @package Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MethodInterceptor()
 */
class CallWithInterceptedObjectInterceptorExample extends BaseInterceptorExample
{
    /**
     * @param StubCallSavingService $stubCallSavingService
     * @Around()
     */
    public function callWithInterceptedObject(StubCallSavingService $stubCallSavingService) : void
    {
        return;
    }
}