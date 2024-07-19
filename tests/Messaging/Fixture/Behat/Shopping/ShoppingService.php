<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Shopping;

/**
 * Interface ShoppingService
 * @package Test\Ecotone\Messaging\Fixture\Behat\Shopping
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface ShoppingService
{
    /**
     * @param string $productName
     * @return BookWasReserved
     */
    public function reserve(string $productName): BookWasReserved;
}
