<?php

namespace Tests\Ecotone\Modelling\Fixture\CommandHandler\Aggregate;

use Ecotone\Modelling\Attribute\TargetAggregateIdentifier;

/**
 * Class GetShippingAddressQuery
 * @package Tests\Ecotone\Modelling\Fixture\CommandHandler\Aggregate
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GetShippingAddressQuery
{
    #[TargetAggregateIdentifier]
    private $orderId;

    /**
     * GetShippingAddressQuery constructor.
     *
     * @param int $orderId
     */
    private function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @param int $orderId
     *
     * @return GetShippingAddressQuery
     */
    public static function create(int $orderId) : self
    {
        return new self($orderId);
    }
}