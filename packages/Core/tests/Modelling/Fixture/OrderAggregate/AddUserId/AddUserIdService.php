<?php

namespace Test\Ecotone\Modelling\Fixture\OrderAggregate\AddUserId;

use Ecotone\Messaging\Attribute\Interceptor\Before;
use Ecotone\Messaging\Attribute\Interceptor\MethodInterceptor;

class AddUserIdService
{
    #[Before(0, AddUserId::class, true)]
    public function add() : array
    {
        return ["userId" => 1];
    }
}