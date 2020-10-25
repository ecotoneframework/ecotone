<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\AddVat;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ServiceActivator;

class AddVatService
{
    #[ServiceActivator("addVat", "addVatService")]
    public function add(int $amount): int
    {
        return $amount * 2;
    }
}