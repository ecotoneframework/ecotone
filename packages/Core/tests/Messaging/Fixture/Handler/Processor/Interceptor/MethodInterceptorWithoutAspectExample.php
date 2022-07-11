<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\ClassReference;

#[ClassReference('methodInterceptor')]
class MethodInterceptorWithoutAspectExample
{
    public function doSomething(): void
    {
    }
}
