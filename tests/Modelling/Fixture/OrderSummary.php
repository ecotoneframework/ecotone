<?php

namespace Test\Ecotone\Modelling\Fixture;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Annotation\IgnorePayload;
use Ecotone\Modelling\Annotation\QueryHandler;

class OrderSummary
{
    #[QueryHandler]
    #[IgnorePayload]
    public function getOrders(GetOrdersQuery $query) : array
    {
        //return orders
    }
}