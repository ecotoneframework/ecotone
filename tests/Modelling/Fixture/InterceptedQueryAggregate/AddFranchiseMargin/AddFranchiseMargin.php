<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\AddFranchiseMargin;

use Ecotone\Messaging\Attribute\Interceptor\After;

class AddFranchiseMargin
{
    #[After(pointcut: AddFranchise::class)]
    public function add(int $amount): int
    {
        return $amount + 10;
    }
}
