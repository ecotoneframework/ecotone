<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Interceptor;

use Ecotone\Api\Attribute\After;
use Ecotone\Api\Attribute\Before;
use Ecotone\Api\Attribute\ClassReference;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Payload;
use Ecotone\Api\Attribute\Presend;

#[ClassReference('someMethodInterceptor')]
/**
 * licence Apache-2.0
 */
class TransformerInterceptorExample
{
    #[Before(2, ServiceActivatorInterceptorExample::class, true)]
    public function doSomethingBefore(#[Payload] string $name, #[Header('surname')] string $surname): void
    {
    }

    #[After(changeHeaders: true)]
    public function doSomethingAfter(#[Payload] string $name, #[Header('surname')] string $surname): void
    {
    }

    #[Presend(2, ServiceActivatorInterceptorExample::class, true)]
    public function beforeSend(#[Payload] string $name, #[Header('surname')] string $surname): void
    {
    }
}
