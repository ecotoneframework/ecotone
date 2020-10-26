<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Annotation\Interceptor\Around;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;

class CallWithStdClassInterceptorExample extends BaseInterceptorExample
{
    private $calledHeaders;

    #[Around]
    public function callWithStdClass(\stdClass $stdClass) : void
    {

    }

    #[Around]
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