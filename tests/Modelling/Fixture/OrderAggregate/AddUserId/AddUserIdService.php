<?php

namespace Test\Ecotone\Modelling\Fixture\OrderAggregate\AddUserId;

use Ecotone\Messaging\Annotation\Interceptor\Before;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;

/**
 * @MethodInterceptor()
 */
class AddUserIdService
{
    /**
     * @Before(
     *     pointcut="@(Test\Ecotone\Modelling\Fixture\OrderAggregate\AddUserId\AddUserId)",
     *     changeHeaders=true,
     *     precedence=0
     * )
     */
    public function add() : array
    {
        return ["userId" => 1];
    }
}