<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor;

use SimplyCodedSoftware\Messaging\Annotation\Interceptor\Around;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class CallWithRequestMessageInterceptorExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MethodInterceptor()
 */
class CallWithRequestMessageInterceptorExample extends BaseInterceptorExample
{
    /**
     * @param MethodInvocation $methodInvocation
     * @param Message $message
     * @return Message
     * @Around()
     */
    public function callWithRequestMessage(MethodInvocation $methodInvocation, Message $message)
    {
        return $message;
    }
}