<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use Ecotone\Messaging\Attribute\ServiceActivator;

/**
 * Class CallWithAnnotationFromMethodInterceptorExample
 * @package Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class CallWithAnnotationFromMethodInterceptorExample extends BaseInterceptorExample
{
    #[Around]
    public function callWithMethodAnnotation(ServiceActivator $methodAnnotation): void
    {
    }
}
