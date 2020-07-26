<?php

namespace Test\Ecotone\Modelling\Fixture\InterceptedQueryAggregate\AddVat;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ServiceActivator;

class AddVatService
{
    /**
     * @ServiceActivator(endpointId="addVatService", inputChannelName="addVat")
     */
    public function add(int $amount): int
    {
        return $amount * 2;
    }
}