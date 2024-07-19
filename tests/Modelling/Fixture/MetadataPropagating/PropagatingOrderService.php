<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\MetadataPropagating;

/**
 * licence Apache-2.0
 */
final class PropagatingOrderService
{
    public function placeOrderWithPropagation(PropagatingGateway $propagatingGateway): void
    {
        $propagatingGateway->placeOrderWithPropagation([]);
    }

    public function placeOrderWithoutPropagation(PropagatingGateway $propagatingGateway): void
    {
        $propagatingGateway->placeOrderWithoutPropagation([]);
    }
}
