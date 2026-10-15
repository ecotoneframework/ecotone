<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Scheduling;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\Delayed;
use Ecotone\Api\Attribute\EventHandler;

/**
 * licence Apache-2.0
 */
class NotificationService
{
    #[Asynchronous('notifications')]
    #[Delayed(1000 * 60)] // 60 seconds
    #[EventHandler(endpointId: 'notifyOrderWasPlaced')]
    public function notify(OrderWasPlaced $event, CustomNotifier $notifier): void
    {
        $notifier->notify('placedOrder', $event->orderId);
    }
}
