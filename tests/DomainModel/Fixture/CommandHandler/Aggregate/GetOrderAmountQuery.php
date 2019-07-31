<?php

namespace Test\Ecotone\DomainModel\Fixture\CommandHandler\Aggregate;

use Ecotone\DomainModel\Annotation\TargetAggregateIdentifier;

/**
 * Class GetAmountQuery
 * @package Test\Ecotone\DomainModel\Fixture\CommandHandler\Aggregate
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GetOrderAmountQuery
{
    /**
     * @var int
     * @TargetAggregateIdentifier()
     */
    private $orderId;

    /**
     * GetOrderAmountQuery constructor.
     *
     * @param int $orderId
     */
    private function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @param int $orderId
     *
     * @return GetOrderAmountQuery
     */
    public static function createWith(int $orderId) : self
    {
        return new self($orderId);
    }
}