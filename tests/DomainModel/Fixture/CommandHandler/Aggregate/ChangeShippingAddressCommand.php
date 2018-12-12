<?php

namespace Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate;

use SimplyCodedSoftware\DomainModel\Annotation\TargetAggregateIdentifier;

/**
 * Class ChangeShippingAddressCommand
 * @package Test\SimplyCodedSoftware\DomainModel\Fixture\CommandHandler\Aggregate
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ChangeShippingAddressCommand
{
    /**
     * @var string
     * @TargetAggregateIdentifier()
     */
    private $orderId;
    /**
     * @var int
     */
    private $version;
    /**
     * @var string
     */
    private $shippingAddress;

    /**
     * ChangeShippingAddressCommand constructor.
     * @param string $orderId
     * @param int $version
     * @param string $shippingAddress
     */
    private function __construct(string $orderId, int $version, string $shippingAddress)
    {
        $this->orderId = $orderId;
        $this->version = $version;
        $this->shippingAddress = $shippingAddress;
    }

    public static function create(string $orderId, int $version, string $shippingAddress) : self
    {
        return new self($orderId, $version, $shippingAddress);
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
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getShippingAddress(): string
    {
        return $this->shippingAddress;
    }
}