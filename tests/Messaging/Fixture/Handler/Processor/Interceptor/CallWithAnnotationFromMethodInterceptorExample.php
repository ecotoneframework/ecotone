<?php

namespace Tests\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\ServiceActivator;

/**
 * Class CallWithAnnotationFromMethodInterceptorExample
 * @package Tests\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CallWithAnnotationFromMethodInterceptorExample extends BaseInterceptorExample
{
    #[Around]
    public function callWithMethodAnnotation(ServiceActivator $methodAnnotation) : void
    {

    }
}