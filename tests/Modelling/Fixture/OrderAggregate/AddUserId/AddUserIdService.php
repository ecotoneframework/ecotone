<?php

namespace Test\Ecotone\Modelling\Fixture\OrderAggregate\AddUserId;

use Ecotone\Messaging\Attribute\Interceptor\Before;

/**
 * licence Apache-2.0
 */
class AddUserIdService
{
    #[Before(0, AddUserId::class, true)]
    public function add(): array
    {
        return ['userId' => 1];
    }
}
