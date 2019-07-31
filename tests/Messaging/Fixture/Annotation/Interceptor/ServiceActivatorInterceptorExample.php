<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Interceptor;

use Ecotone\Messaging\Annotation\Interceptor\After;
use Ecotone\Messaging\Annotation\Interceptor\Before;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Payload;

/**
 * Class ServiceActivatorInterceptorExample
 * @package Test\Ecotone\Messaging\Fixture\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MethodInterceptor(referenceName="someMethodInterceptor")
 */
class ServiceActivatorInterceptorExample
{
    /**
     * @Before(precedence=2, pointcut=ServiceActivatorInterceptorExample::class, parameterConverters={
     *      @Payload(parameterName="name"),
     *      @Header(parameterName="surname", headerName="surname")
     * })
     * @param string $name
     * @param string $surname
     */
    public function doSomethingBefore(string $name, string $surname) : void
    {

    }

    /**
     * @After(parameterConverters={
     *      @Payload(parameterName="name"),
     *      @Header(parameterName="surname", headerName="surname")
     * })
     * @param string $name
     * @param string $surname
     */
    public function doSomethingAfter(string $name, string $surname) : void
    {

    }
}