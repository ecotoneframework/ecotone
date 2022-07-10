<?php

namespace Test\Ecotone\Modelling\Fixture\TwoSagas;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Aggregate]
class Bookkeeping
{
    const GET_BOOKING_STATUS = "getBookingStatus";
    #[AggregateIdentifier]
    private string $orderId;
    private string $status;

    private function __construct(string $orderId)
    {
        $this->orderId  = $orderId;
        $this->status = "awaitingPayment";
    }

    #[EventHandler]
    public static function createWith(OrderWasPlaced $event) : self
    {
        return new self($event->getOrderId());
    }

    #[EventHandler]
    public function when(OrderWasPaid $event) : void
    {
        if ($this->status === "paid") {
            throw new \InvalidArgumentException("Trying to pay second time");
        }

        $this->status = "paid";
    }

    #[QueryHandler(self::GET_BOOKING_STATUS)]
    public function getStatus() : string
    {
        return $this->status;
    }

    public function getId() : string
    {
        return $this->orderId;
    }
}