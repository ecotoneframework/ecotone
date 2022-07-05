<?php

namespace Ecotone\Tests\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\ClassReference;
use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;

#[ClassReference("methodInterceptor")]
class MethodInterceptorWithoutAspectExample
{
    public function doSomething() : void
    {

    }
}