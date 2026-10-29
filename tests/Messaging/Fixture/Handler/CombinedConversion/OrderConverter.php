<?php

namespace Test\Ecotone\Messaging\Fixture\Handler\CombinedConversion;

use Ecotone\Messaging\Attribute\Converter;

/**
 * Class OrderConverter
 * @package Fixture\Handler\CombinedConversion
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class OrderConverter
{
    #[Converter]
    public function convertFromArrayToObject(array $data): Order
    {
        return new Order($data['orderId'], $data['name']);
    }

    #[Converter]
    public function fromObjectToArray(Order $order): array
    {
        return [
            'orderId' => $order->getOrderId(),
            'name' => $order->getName(),
        ];
    }
}
