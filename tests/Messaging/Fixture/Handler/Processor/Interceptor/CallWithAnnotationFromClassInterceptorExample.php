<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Annotation\Interceptor\Around;
use Ecotone\Messaging\Annotation\MessageEndpoint;

/**
 * Class CallWithAnnotationFromMethodInterceptorExample
 * @package Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CallWithAnnotationFromClassInterceptorExample extends BaseInterceptorExample
{
    /**
     * @param MessageEndpoint $messageEndpoint
     * @Around()
     */
    public function callWithMethodAnnotation(MessageEndpoint $messageEndpoint) : void
    {

    }
}