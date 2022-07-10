<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Interceptor;

use Ecotone\Messaging\Attribute\ClassReference;
use Ecotone\Messaging\Attribute\Interceptor\After;
use Ecotone\Messaging\Attribute\Interceptor\Before;
use Ecotone\Messaging\Attribute\Interceptor\Presend;
use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Attribute\Interceptor\ServiceActivatorInterceptor;
use Ecotone\Messaging\Attribute\Interceptor\TransformerInterceptor;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Attribute\Parameter\Payload;

#[ClassReference("someMethodInterceptor")]
class TransformerInterceptorExample
{
    #[Before(2, ServiceActivatorInterceptorExample::class, true)]
    public function doSomethingBefore(#[Payload] string $name, #[Header("surname")] string $surname) : void
    {

    }

    #[After(changeHeaders: true)]
    public function doSomethingAfter(#[Payload] string $name, #[Header("surname")] string $surname) : void
    {

    }

    #[Presend(2, ServiceActivatorInterceptorExample::class, true)]
    public function beforeSend(#[Payload] string $name, #[Header("surname")] string $surname) : void
    {

    }
}