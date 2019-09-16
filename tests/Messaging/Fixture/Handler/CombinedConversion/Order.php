<?php


namespace Test\Ecotone\Messaging\Fixture\Handler\CombinedConversion;

class Order implements OrderInterface
{
    /**
     * @var string
     */
    private $orderId;
    /**
     * @var string
     */
    private $name;

    /**
     * Order constructor.
     * @param string $orderId
     * @param string $name
     */
    public function __construct(string $orderId, string $name)
    {
        $this->orderId = $orderId;
        $this->name = $name;
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
    public function getName(): string
    {
        return $this->name;
    }
}