<?php


namespace Test\Ecotone\Modelling\Fixture\TwoAsynchronousSagas;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

#[Asynchronous(MessagingConfiguration::ASYNCHRONOUS_CHANNEL)]
#[Aggregate]
class Shipment
{
    const GET_SHIPMENT_STATUS = "getShipmentStatus";
    #[AggregateIdentifier]
    private string $orderId;
    private string $status;

    private function __construct(string $orderId)
    {
        $this->orderId  = $orderId;
        $this->status = "awaitingPayment";
    }

    #[EventHandler(endpointId: "Shipment::createWith")]
    public static function createWith(OrderWasPlaced $event) : self
    {
        return new self($event->getOrderId());
    }

    #[EventHandler(endpointId: "Shipment::when")]
    public function when(OrderWasPaid $event) : void
    {
        if ($this->status === "shipped") {
            throw new \InvalidArgumentException("Trying to ship second time");
        }

        $this->status = "shipped";
    }

    #[QueryHandler(self::GET_SHIPMENT_STATUS)]
    public function getStatus() : string
    {
        return $this->status;
    }

    public function getId() : string
    {
        return $this->orderId;
    }
}