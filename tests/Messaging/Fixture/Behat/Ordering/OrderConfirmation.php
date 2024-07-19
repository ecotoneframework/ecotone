<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Ordering;

use Ramsey\Uuid\UuidInterface;

/**
 * Class OrderConfirmation
 * @package Test\Ecotone\Messaging\Fixture\Behat\Ordering
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class OrderConfirmation
{
    /**
     * @var string
     */
    private $orderId;

    /**
     * Order constructor.
     * @param string $orderId
     */
    private function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @param Order $order
     * @return OrderConfirmation
     */
    public static function fromOrder(Order $order): self
    {
        return new self($order->getOrderId());
    }

    /**
     * @param UuidInterface $orderId
     * @return OrderConfirmation
     */
    public static function createFromUuid(UuidInterface $orderId): self
    {
        return new self($orderId->toString());
    }
}
