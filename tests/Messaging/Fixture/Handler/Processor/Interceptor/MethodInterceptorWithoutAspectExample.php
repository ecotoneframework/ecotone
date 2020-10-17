<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Annotation\ClassReference;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;

#[ClassReference("methodInterceptor")]
class MethodInterceptorWithoutAspectExample
{
    public function doSomething() : void
    {

    }
}