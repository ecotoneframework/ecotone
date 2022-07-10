<?php

namespace Test\Ecotone\Modelling\Fixture\Saga;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\AggregateIdentifier;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\EventHandler;

#[Aggregate]
class OrderFulfilment
{
    #[AggregateIdentifier]
    private $orderId;
    /**
     * @var string
     */
    private $status;

    /**
     * Article constructor.
     *
     * @param string $orderId
     */
    private function __construct(string $orderId)
    {
        $this->orderId  = $orderId;
        $this->status = "new";
    }

    #[EventHandler]
    public static function createWith(string $orderId) : self
    {
        return new self($orderId);
    }

    #[EventHandler]
    public function finishOrder(PaymentWasDoneEvent $event) : void
    {
        $this->status = "done";
    }

    public function getId() : string
    {
        return $this->orderId;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }
}