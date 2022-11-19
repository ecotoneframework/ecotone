<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Order;

use Ecotone\Messaging\Attribute\Converter;

final class PlaceOrderConverter
{
    #[Converter]
    public function toPlaceOrder(array $data): PlaceOrder
    {
        return new PlaceOrder($data['orderId']);
    }

    #[Converter]
    public function fromPlaceOrder(PlaceOrder $data): array
    {
        return ["orderId" => $data->getOrderId()];
    }
}