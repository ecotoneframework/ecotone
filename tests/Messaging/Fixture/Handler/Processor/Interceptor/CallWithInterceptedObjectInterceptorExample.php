<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor;

use SimplyCodedSoftware\Messaging\Annotation\Interceptor\Around;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptor;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\StubCallSavingService;

/**
 * Class CallWithInterceptedObjectInterceptorExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor
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