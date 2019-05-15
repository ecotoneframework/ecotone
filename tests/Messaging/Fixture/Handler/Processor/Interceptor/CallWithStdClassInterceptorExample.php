<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor;

use SimplyCodedSoftware\Messaging\Annotation\Interceptor\Around;
use SimplyCodedSoftware\Messaging\Annotation\Interceptor\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

/**
 * Class CallWithNullableStdClassInterceptorExample
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @MethodInterceptor()
 */
class CallWithStdClassInterceptorExample extends BaseInterceptorExample
{
    private $calledHeaders;

    /**
     * @param \stdClass|null $stdClass
     * @Around()
     */
    public function callWithStdClass(\stdClass $stdClass) : void
    {

    }

    /**
     * @param \stdClass|null $stdClass
     * @param array          $headers
     * @Around()
     *
     * @return void
     */
    public function callWithStdClassAndHeaders(\stdClass $stdClass, array $headers) : void
    {
        $this->calledHeaders = $headers;
    }

    /**
     * @return mixed
     */
    public function getCalledHeaders()
    {
        return $this->calledHeaders;
    }
}