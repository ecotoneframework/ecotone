<?php

namespace Test\Ecotone\Modelling\Fixture\TwoSagas;

use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Saga;
use InvalidArgumentException;

#[Saga]
/**
 * licence Apache-2.0
 */
class Bookkeeping
{
    public const GET_BOOKING_STATUS = 'getBookingStatus';
    #[Identifier]
    private string $orderId;
    private string $status;

    private function __construct(string $orderId)
    {
        $this->orderId  = $orderId;
        $this->status = 'awaitingPayment';
    }

    #[EventHandler]
    public static function createWith(OrderWasPlaced $event): self
    {
        return new self($event->getOrderId());
    }

    #[EventHandler]
    public function when(OrderWasPaid $event): void
    {
        if ($this->status === 'paid') {
            throw new InvalidArgumentException('Trying to pay second time');
        }

        $this->status = 'paid';
    }

    #[QueryHandler(self::GET_BOOKING_STATUS)]
    public function getStatus(): string
    {
        return $this->status;
    }

    public function getId(): string
    {
        return $this->orderId;
    }
}
