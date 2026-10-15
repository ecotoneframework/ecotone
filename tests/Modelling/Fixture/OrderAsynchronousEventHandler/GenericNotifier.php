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
final class GenericNotifier
{
    /** @var OrderWasPlaced[] */
    private $notifiedOrders = [];

    #[Asynchronous('notifications')]
    #[EventHandler(endpointId: 'orderNotifierOrderWasPlaced')]
    public function handle(OrderWasPlaced $event): void
    {
        $this->notifiedOrders[] = $event;
    }

    /**
     * @return OrderWasPlaced[]
     */
    #[QueryHandler('order.getNotifications')]
    public function getNotifications(): array
    {
        return $this->notifiedOrders;
    }
}
