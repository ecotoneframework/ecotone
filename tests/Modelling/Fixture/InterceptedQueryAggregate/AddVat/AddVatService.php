<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\AddVat;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Attribute\ServiceActivator;

class AddVatService
{
    #[ServiceActivator("addVat", "addVatService")]
    public function add(int $amount): int
    {
        return $amount * 2;
    }
}