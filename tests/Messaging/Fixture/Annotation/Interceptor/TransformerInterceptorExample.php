<?php

namespace Test\Ecotone\Messaging\Fixture\Annotation\Interceptor;

use Ecotone\Messaging\Annotation\ClassReference;
use Ecotone\Messaging\Annotation\Interceptor\After;
use Ecotone\Messaging\Annotation\Interceptor\Before;
use Ecotone\Messaging\Annotation\Interceptor\Presend;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Annotation\Interceptor\ServiceActivatorInterceptor;
use Ecotone\Messaging\Annotation\Interceptor\TransformerInterceptor;
use Ecotone\Messaging\Annotation\Parameter\Header;
use Ecotone\Messaging\Annotation\Parameter\Payload;

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