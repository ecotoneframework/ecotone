<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\OrderAsynchronousEventHandler;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
use Test\Ecotone\Modelling\Fixture\Order\OrderWasPlaced;

/**
 * licence Apache-2.0
 */
final class SmsNotifier
{
    /** @var OrderWasPlaced[] */
    private $notifiedOrders = [];

    #[Asynchronous('notifications')]
    #[EventHandler(endpointId: 'smsNotifier.handle')]
    public function handle(OrderWasPlaced $event): void
    {
        $this->notifiedOrders[] = $event;
    }

    /**
     * @return OrderWasPlaced[]
     */
    #[QueryHandler('order.getSms')]
    public function getNotifications(): array
    {
        return $this->notifiedOrders;
    }
}
