<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Collector;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\EventHandler;

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
