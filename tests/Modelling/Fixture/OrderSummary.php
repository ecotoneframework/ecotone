<?php

namespace Test\Ecotone\Modelling\Fixture;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\IgnorePayload;
use Ecotone\Modelling\Attribute\QueryHandler;

class OrderSummary
{
    #[QueryHandler]
    #[IgnorePayload]
    public function getOrders(GetOrdersQuery $query) : array
    {
        //return orders
    }
}