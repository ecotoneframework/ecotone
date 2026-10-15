<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Interceptor;

use Ecotone\Api\Attribute\After;
use Ecotone\Api\Attribute\Before;
use Ecotone\Api\Attribute\ClassReference;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Payload;

#[ClassReference('someMethodInterceptor')]
/**
 * licence Apache-2.0
 */
class ServiceActivatorInterceptorExample
{
    #[Before(2, ServiceActivatorInterceptorExample::class)]
    public function doSomethingBefore(#[Payload] string $name, #[Header('surname')] string $surname): void
    {
    }

    #[After]
    public function doSomethingAfter(#[Payload] string $name, #[Header('surname')] string $surname): void
    {
    }
}
