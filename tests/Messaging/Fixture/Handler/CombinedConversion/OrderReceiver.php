<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\CombinedConversion;

/**
 * licence Apache-2.0
 */
class OrderReceiver
{
    public function receive(OrderInterface $order): OrderInterface
    {
        return $order;
    }
}
