<?php


namespace Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\AddFranchiseMargin;

use Ecotone\Messaging\Annotation\Interceptor\After;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;

/**
 * @MethodInterceptor()
 */
class AddFranchiseMargin
{
    /**
     * @After(pointcut="@(Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\AddFranchiseMargin\AddFranchise)")
     */
    public function add(int $amount) : int
    {
        return $amount + 10;
    }
}