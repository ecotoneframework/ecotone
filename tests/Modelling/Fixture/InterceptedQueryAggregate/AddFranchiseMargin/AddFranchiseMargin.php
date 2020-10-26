<?php


namespace Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\AddFranchiseMargin;

use Ecotone\Messaging\Annotation\Interceptor\After;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;

class AddFranchiseMargin
{
    #[After(pointcut: AddFranchise::class)]
    public function add(int $amount) : int
    {
        return $amount + 10;
    }
}