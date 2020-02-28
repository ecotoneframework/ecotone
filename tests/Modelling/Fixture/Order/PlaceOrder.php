<?php


namespace Test\Ecotone\Modelling\Fixture\Order;


class PlaceOrder
{
    /**
     * @var string
     */
    private $personId;

    /**
     * PlaceOrder constructor.
     * @param string $personId
     */
    public function __construct(string $personId)
    {
        $this->personId = $personId;
    }

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->personId;
    }
}