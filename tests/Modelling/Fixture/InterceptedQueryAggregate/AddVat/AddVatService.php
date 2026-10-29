<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\AddVat;

use Ecotone\Messaging\Attribute\ServiceActivator;

/**
 * licence Apache-2.0
 */
class AddVatService
{
    #[ServiceActivator('addVat', 'addVatService')]
    public function add(int $amount): int
    {
        return $amount * 2;
    }
}
