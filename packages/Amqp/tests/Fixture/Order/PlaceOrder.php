<?php

namespace Test\Ecotone\Amqp\Fixture\Order;

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
    public function getPersonId(): string
    {
        return $this->personId;
    }
}
