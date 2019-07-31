<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Ordering;

/**
 * Class Order
 * @package Test\Ecotone\Messaging\Fixture\Behat\Ordering
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class Order
{
    /**
     * @var string
     */
    private $orderId;
    /**
     * @var string
     */
    private $productName;

    /**
     * Order constructor.
     * @param string $orderId
     * @param string $productName
     */
    private function __construct(string $orderId, string $productName)
    {
        $this->orderId = $orderId;
        $this->productName = $productName;
    }

    /**
     * @param string $orderId
     * @param string $productName
     * @return Order
     */
    public static function create(string $orderId, string $productName) : self
    {
        return new self($orderId, $productName);
    }

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * @return string
     */
    public function getProductName(): string
    {
        return $this->productName;
    }
}