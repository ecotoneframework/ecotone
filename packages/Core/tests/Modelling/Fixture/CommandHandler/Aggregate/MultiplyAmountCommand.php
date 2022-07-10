<?php

namespace Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate;
use Ecotone\Modelling\Attribute\TargetAggregateVersion;
use Ecotone\Modelling\Attribute\TargetAggregateIdentifier;

/**
 * Class MultiplyAmountCommand
 * @package Test\Ecotone\Modelling\Fixture\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class MultiplyAmountCommand
{
    #[TargetAggregateIdentifier]
    private $orderId;
    #[TargetAggregateVersion]
    private $version;
    /**
     * @var int
     */
    private $amount;

    /**
     * MultiplyAmountCommand constructor.
     * @param string $orderId
     * @param int $version
     * @param int $amount
     */
    private function __construct(string $orderId, ?int $version, int $amount)
    {
        $this->orderId = $orderId;
        $this->version = $version;
        $this->amount = $amount;
    }

    /**
     * @param string $orderId
     * @param int $version
     * @param int $amount
     * @return MultiplyAmountCommand
     */
    public static function create(string $orderId, ?int $version, int $amount) : self
    {
        return new self($orderId, $version, $amount);
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
    public function getVersion(): ?int
    {
        return $this->version;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }
}