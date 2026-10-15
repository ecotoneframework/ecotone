<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\OrderAsynchronousEventHandler;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\QueryHandler;
use Test\Ecotone\Modelling\Fixture\Order\OrderWasPlaced;

/**
 * licence Apache-2.0
 */
final class ShippingEventHandler
{
    /**
     * @var OrderWasPlaced[]
     */
    private $shippings = [];

    #[Asynchronous('shipping')]
    #[EventHandler(endpointId: 'shipping.ship')]
    public function ship(OrderWasPlaced $event): void
    {
        $this->shippings[] = $event;
    }

    /**
     * @return OrderWasPlaced[]
     */
    #[QueryHandler('order.getShippings')]
    public function getShippings(): array
    {
        return $this->shippings;
    }
}
