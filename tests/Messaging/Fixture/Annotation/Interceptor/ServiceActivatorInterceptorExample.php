<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Interceptor;

use Ecotone\Messaging\Annotation\ClassReference;
use Ecotone\Messaging\Annotation\Interceptor\After;
use Ecotone\Messaging\Annotation\Interceptor\Before;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Payload;

#[ClassReference("someMethodInterceptor")]
class ServiceActivatorInterceptorExample
{
    /**
     * @Before(precedence=2, pointcut=ServiceActivatorInterceptorExample::class)
     */
    public function doSomethingBefore(#[Payload] string $name, #[Header("surname")] string $surname) : void
    {

    }

    /**
     * @After()
     */
    public function doSomethingAfter(#[Payload] string $name, #[Header("surname")] string $surname) : void
    {

    }
}