<?php

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Ordering;
use SimplyCodedSoftware\Messaging\Future;

/**
 * Interface OrderingService
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Behat\Ordering
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