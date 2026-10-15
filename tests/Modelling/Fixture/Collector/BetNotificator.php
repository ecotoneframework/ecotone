<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Collector;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\EventHandler;

/**
 * licence Apache-2.0
 */
final class BetNotificator
{
    #[Asynchronous('notifications')]
    #[EventHandler(endpointId: 'betNotifications')]
    public function notify(BetPlaced $event): void
    {

    }
}
