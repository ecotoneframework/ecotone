<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Interceptor;

use Ecotone\Api\Attribute\After;
use Ecotone\Api\Attribute\Before;
use stdClass;

/**
 * licence Apache-2.0
 */
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
