<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate;

use SimplyCodedSoftware\DomainModel\Annotation\TargetAggregateIdentifier;

/**
 * Class GetShippingAddressQuery
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GetShippingAddressQuery
{
    /**
     * @var int
     * @TargetAggregateIdentifier()
     */
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