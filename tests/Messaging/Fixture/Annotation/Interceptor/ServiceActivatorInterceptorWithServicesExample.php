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
 * @MethodInterceptor()
 */
class ServiceActivatorInterceptorWithServicesExample
{
    /**
     * @Before(precedence=2)
     * @param string $name
     * @param array $metadata
     * @param \stdClass $stdClass
     */
    public function doSomethingBefore(string $name, array $metadata, \stdClass $stdClass) : void
    {

    }

    /**
     * @After(precedence=2)
     * @param string $name
     * @param \stdClass $stdClass
     */
    public function doSomethingAfter(string $name, \stdClass $stdClass) : void
    {

    }
}