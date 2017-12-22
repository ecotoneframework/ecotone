<?php

namespace Fixture\Behat\Ordering;

/**
 * Class OrderProcessor
 * @package Fixture\Behat\Ordering
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class OrderProcessor
{
    public function processOrder(Order $order) : OrderConfirmation
    {
        if (!$this->isCorrectOrder($order)) {
            throw new \RuntimeException("Order is not correct!");
        }

        return OrderConfirmation::fromOrder($order);
    }

    private function isCorrectOrder(Order $order) : bool
    {
        return $order->getProductName() === 'correct';
    }
}