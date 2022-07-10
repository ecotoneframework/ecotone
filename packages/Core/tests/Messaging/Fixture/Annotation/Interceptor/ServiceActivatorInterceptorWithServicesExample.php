<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Interceptor;

use Ecotone\Messaging\Attribute\Interceptor\After;
use Ecotone\Messaging\Attribute\Interceptor\Before;
use stdClass;

class ServiceActivatorInterceptorWithServicesExample
{
    #[Before(2)]
    public function doSomethingBefore(string $name, array $metadata, stdClass $stdClass): void
    {

    }

    #[After(2)]
    public function doSomethingAfter(string $name, stdClass $stdClass): void
    {

    }
}