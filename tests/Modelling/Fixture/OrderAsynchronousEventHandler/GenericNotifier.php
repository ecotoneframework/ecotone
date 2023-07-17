<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\OrderAsynchronousEventHandler;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Test\Ecotone\Modelling\Fixture\Order\OrderWasPlaced;

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
