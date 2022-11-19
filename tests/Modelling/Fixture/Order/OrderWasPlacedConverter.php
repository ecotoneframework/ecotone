<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Order;

use Ecotone\Messaging\Attribute\Converter;

final class OrderWasPlacedConverter
{
    #[Converter]
    public function toOrderWasPlaced(array $data): OrderWasPlaced
    {
        return new OrderWasPlaced($data['orderId']);
    }

    #[Converter]
    public function fromOrderWasPlaced(OrderWasPlaced $data): array
    {
        return ['orderId' => $data->getOrderId()];
    }
}
