<?php

namespace Test\Ecotone\Modelling\Fixture;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\QueryHandler;

class OrderSummary
{
    /**
     * @QueryHandler(ignorePayload=true)
     */
    public function getOrders(GetOrdersQuery $query) : array
    {
        //return orders
    }
}