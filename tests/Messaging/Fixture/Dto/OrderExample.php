<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Dto;

use Test\Ecotone\Messaging\Fixture\Dto\WithCustomer\Customer;

/**
 * Class Order
 * @package Test\Ecotone\Messaging\Fixture\Dto
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class OrderExample
{
    /**
     * @var int
     */
    private $orderId;
    /**
     * @var int
     */
    private $quantity;
    /**
     * @var string
     */
    private $buyerName;

    /**
     * Order constructor.
     *
     * @param int    $orderId
     * @param int    $quantity
     * @param string $buyerName
     */
    private function __construct(int $orderId, int $quantity, string $buyerName)
    {
        $this->orderId   = $orderId;
        $this->quantity  = $quantity;
        $this->buyerName = $buyerName;
    }

    /**
     * @param int    $orderId
     * @param int    $quantity
     * @param string $buyName
     *
     * @return OrderExample
     */
    public static function createWith(int $orderId, int $quantity, string $buyName) : self
    {
        return new self($orderId, $quantity, $buyName);
    }

    /**
     * @param OrderExample $orderExample
     * @return bool
     */
    public function isSameAs(OrderExample $orderExample) : bool
    {
        return $this == $orderExample;
    }

    /**
     * @param Customer $customer
     */
    public function changeBuyer(Customer $customer) : void
    {
        $this->buyerName = $customer->getUsername();
    }

    /**
     * @param int $orderId
     *
     * @return OrderExample
     */
    public static function createFromId(int $orderId) : self
    {
        return new self($orderId, 1, "");
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->orderId;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity(int $quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return string
     */
    public function getBuyerName(): string
    {
        return $this->buyerName;
    }

    /**
     * @param string $buyerName
     */
    public function setBuyerName(string $buyerName)
    {
        $this->buyerName = $buyerName;
    }
}