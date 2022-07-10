<?php


namespace Test\Ecotone\Messaging\Fixture\Handler\CombinedConversion;


class OrderIdIncreaser
{
    public function increase(array $order) : array
    {
        $order['orderId'] = $order['orderId'] + 1;

        return $order;
    }
}