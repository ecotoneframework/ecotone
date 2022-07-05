<?php

namespace Ecotone\Tests\Messaging\Fixture\Behat\Ordering;
use Ecotone\Messaging\Future;

/**
 * Interface OrderingService
 * @package Ecotone\Tests\Messaging\Fixture\Behat\Ordering
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface OrderingService
{
    /**
     * @param Order $order
     * @return Future
     */
    public function processOrder(Order $order) : Future;
}