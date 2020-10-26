<?php

namespace Test\Ecotone\Modelling\Fixture\OrderAggregate\AddUserId;

use Ecotone\Messaging\Annotation\Interceptor\Before;
use Ecotone\Messaging\Annotation\Interceptor\MethodInterceptor;

class AddUserIdService
{
    #[Before(0, "@(" . AddUserId::class . ")", true)]
    public function add() : array
    {
        return ["userId" => 1];
    }
}