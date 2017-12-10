<?php

namespace Fixture\Behat\Shopping;

/**
 * Interface ShoppingService
 * @package Fixture\Behat\Shopping
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ShoppingService
{
    /**
     * @param string $productName
     * @return BookWasReserved
     */
    public function reserve(string $productName) : BookWasReserved;
}