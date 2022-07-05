<?php

namespace Ecotone\Tests\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\Interceptor\Around;

/**
 * Class AspectWithoutMethodInterceptorExample
 * @package Ecotone\Tests\Messaging\Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AspectWithoutMethodInterceptorExample
{
    #[Around]
    public function doSomething() : void
    {

    }
}