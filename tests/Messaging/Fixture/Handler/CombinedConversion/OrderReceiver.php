<?php
declare(strict_types=1);


namespace Ecotone\Tests\Messaging\Fixture\Handler\CombinedConversion;


class OrderReceiver
{
    public function receive(OrderInterface $order) : OrderInterface
    {
        return $order;
    }
}