<?php

namespace Test\Ecotone\Modelling\Fixture\Saga;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Saga;

#[Saga]
/**
 * licence Apache-2.0
 */
class OrderFulfilment
{
    #[Identifier]
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
        $this->status = 'new';
    }

    #[CommandHandler('order.start')]
    public static function createWith(string $orderId): self
    {
        return new self($orderId);
    }

    #[EventHandler(identifierMetadataMapping: ['orderId' => 'paymentId'])]
    public function finishOrder(PaymentWasDoneEvent $event): void
    {
        $this->status = 'done';
    }

    public function getId(): string
    {
        return $this->orderId;
    }

    /**
     * @return string
     */
    #[QueryHandler('order.status')]
    public function getStatus(): string
    {
        return $this->status;
    }
}
