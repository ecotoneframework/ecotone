<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Annotation\Interceptor\Around;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Message;

/**
 * Class CallWithPassThroughInterceptorExample
 * @package Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MethodInterceptor()
 */
class CallWithPassThroughInterceptorExample extends BaseInterceptorExample
{
    /**
     * @Around()
     * @param Message $message
     */
    public function callWithPassThrough(Message $message) : void
    {

    }
}