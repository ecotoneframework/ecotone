<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Interceptor;

use Ecotone\Messaging\Annotation\ClassReference;
use Ecotone\Messaging\Annotation\Interceptor\After;
use Ecotone\Messaging\Annotation\Interceptor\Before;
use Ecotone\Messaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use stdClass;

class ServiceActivatorInterceptorWithServicesExample
{
    /**
     * @Before(precedence=2)
     * @param string    $name
     * @param array     $metadata
     * @param stdClass $stdClass
     */
    public function doSomethingBefore(string $name, array $metadata, stdClass $stdClass): void
    {

    }

    /**
     * @After(precedence=2)
     * @param string    $name
     * @param stdClass $stdClass
     */
    public function doSomethingAfter(string $name, stdClass $stdClass): void
    {

    }
}