<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Interceptor;

use Ecotone\Messaging\Annotation\Interceptor\After;
use Ecotone\Messaging\Annotation\Interceptor\Before;
use Ecotone\Messaging\Annotation\Interceptor\BeforeSend;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use Ecotone\Messaging\Annotation\Interceptor\TransformerInterceptor;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Payload;

/**
 * Class ServiceActivatorInterceptorExample
 * @package Test\Ecotone\Messaging\Fixture\Annotation\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MethodInterceptor(referenceName="someMethodInterceptor")
 */
class TransformerInterceptorExample
{
    /**
     * @Before(precedence=2, pointcut=ServiceActivatorInterceptorExample::class, parameterConverters={
     *      @Payload(parameterName="name"),
     *      @Header(parameterName="surname", headerName="surname")
     * }, changeHeaders=true)
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
     * }, changeHeaders=true)
     * @param string $name
     * @param string $surname
     */
    public function doSomethingAfter(string $name, string $surname) : void
    {

    }

    /**
     * @BeforeSend(precedence=2, pointcut=ServiceActivatorInterceptorExample::class, parameterConverters={
     *      @Payload(parameterName="name"),
     *      @Header(parameterName="surname", headerName="surname")
     * }, changeHeaders=true)
     * @param string $name
     * @param string $surname
     */
    public function beforeSend(string $name, string $surname) : void
    {

    }
}