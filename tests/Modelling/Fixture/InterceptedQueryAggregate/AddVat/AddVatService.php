<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\AddVat;

use Ecotone\Api\Attribute\InternalHandler;

/**
 * licence Apache-2.0
 */
class AddVatService
{
    #[InternalHandler('addVat', endpointId: 'addVatService')]
    public function add(int $amount): int
    {
        return $amount * 2;
    }
}
