<?php

namespace Test\Ecotone\DomainModel\Fixture\CommandHandler\Aggregate;

/**
 * Interface RegisterWithShipping
 * @package Test\Ecotone\DomainModel\Fixture\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface RegisterWithShipping
{
    /**
     * @return string
     */
    public function getOrderId(): string;

    /**
     * @return string
     */
    public function getShippingAddress(): string;
}