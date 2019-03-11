<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor;

use SimplyCodedSoftware\Messaging\Annotation\Interceptor\Around;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;

/**
 * Class CallWithAnnotationFromMethodInterceptorExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor
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