<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\Processor\Interceptor;

use Ecotone\Messaging\Attribute\Interceptor\Around;
use stdClass;

class CallWithStdClassInterceptorExample extends BaseInterceptorExample
{
    private $calledHeaders;

    #[Around]
    public function callWithStdClass(stdClass $stdClass): void
    {
    }

    #[Around]
    public function callWithStdClassAndHeaders(stdClass $stdClass, array $headers): void
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
