<?php

namespace Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate;

class IncreaseAmountCommand
{
    /**
     * @var string
     */
    private $orderId;
    /**
     * @var int
     */
    private $amount;

    private function __construct(string $orderId, int $amount)
    {
        $this->orderId = $orderId;
        $this->amount = $amount;
    }

    public static function createWith(string $aggregateId, int $amount) : self
    {
        return new self($aggregateId, $amount);
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
}