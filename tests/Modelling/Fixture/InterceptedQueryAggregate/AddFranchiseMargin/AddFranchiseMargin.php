<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\AddFranchiseMargin;

use Ecotone\Messaging\Attribute\Interceptor\After;

/**
 * licence Apache-2.0
 */
class AddFranchiseMargin
{
    #[After(pointcut: AddFranchise::class)]
    public function add(int $amount): int
    {
        return $amount + 10;
    }
}
