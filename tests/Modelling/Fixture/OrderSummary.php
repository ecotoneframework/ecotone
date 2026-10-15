<?php

namespace Test\Ecotone\Modelling\Fixture;

use Ecotone\Api\Attribute\IgnorePayload;
use Ecotone\Api\Attribute\QueryHandler;

/**
 * licence Apache-2.0
 */
class OrderSummary
{
    #[QueryHandler]
    #[IgnorePayload]
    public function getOrders(GetOrdersQuery $query): array
    {
        //return orders
    }
}
