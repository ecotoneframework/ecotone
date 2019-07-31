<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Annotation\Interceptor\Around;
use Ecotone\Messaging\Annotation\ServiceActivator;

/**
 * Class CallWithAnnotationFromMethodInterceptorExample
 * @package Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CallWithAnnotationFromMethodInterceptorExample extends BaseInterceptorExample
{
    /**
     * @param ServiceActivator $methodAnnotation
     * @Around()
     */
    public function callWithMethodAnnotation(ServiceActivator $methodAnnotation) : void
    {

    }
}