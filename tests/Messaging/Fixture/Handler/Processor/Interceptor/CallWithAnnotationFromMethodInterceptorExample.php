<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor;

use SimplyCodedSoftware\Messaging\Annotation\Interceptor\Around;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivator;

/**
 * Class CallWithAnnotationFromMethodInterceptorExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor
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