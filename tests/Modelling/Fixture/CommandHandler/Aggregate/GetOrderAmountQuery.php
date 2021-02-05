<?php

namespace Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate;

use Ecotone\Modelling\Attribute\TargetAggregateIdentifier;

/**
 * Class GetAmountQuery
 * @package Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GetOrderAmountQuery
{
    #[TargetAggregateIdentifier]
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