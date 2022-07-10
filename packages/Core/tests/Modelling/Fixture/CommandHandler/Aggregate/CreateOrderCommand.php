<?php

namespace Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate;

/**
 * Class CreateOrderCommand
 * @package Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CreateOrderCommand
{
    /**
     * @var string
     */
    private $orderId;
    /**
     * @var int
     */
    private $amount;
    /**
     * @var string
     */
    private $shippingAddress;

    /**
     * CreateOrderCommand constructor.
     * @param string $orderId
     * @param int $amount
     * @param string $shippingAddress
     */
    private function __construct(string $orderId, int $amount, string $shippingAddress)
    {
        $this->orderId = $orderId;
        $this->amount = $amount;
        $this->shippingAddress = $shippingAddress;
    }

    /**
     * @param string $aggregateId
     * @param int $amount
     * @param string $shippingAddress
     * @return CreateOrderCommand
     */
    public static function createWith(string $aggregateId, int $amount, string $shippingAddress) : self
    {
        return new self($aggregateId, $amount, $shippingAddress);
    }

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getShippingAddress(): string
    {
        return $this->shippingAddress;
    }
}