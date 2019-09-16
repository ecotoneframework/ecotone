<?php


namespace Test\Ecotone\Messaging\Fixture\Handler\CombinedConversion;

/**
 * Class OrderConverter
 * @package Fixture\Handler\CombinedConversion
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class OrderConverter
{
    public function convertFromArrayToObject(array $data) : Order
    {
        return new Order($data['orderId'], $data['name']);
    }

    public function fromObjectToArray(Order $order) : array
    {
        return [
            "orderId" => $order->getOrderId(),
            "name" => $order->getName()
        ];
    }
}