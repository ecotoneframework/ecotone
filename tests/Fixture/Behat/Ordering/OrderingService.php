<?php

namespace Fixture\Behat\Ordering;
use SimplyCodedSoftware\IntegrationMessaging\Future;

/**
 * Interface OrderingService
 * @package Fixture\Behat\Ordering
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