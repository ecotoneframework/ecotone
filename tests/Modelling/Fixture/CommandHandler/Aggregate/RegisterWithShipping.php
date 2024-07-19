<?php

namespace Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate;

/**
 * Interface RegisterWithShipping
 * @package Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
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
