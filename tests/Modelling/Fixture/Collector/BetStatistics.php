<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\Collector;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Modelling\Attribute\EventHandler;

final class BetStatistics
{
    #[Asynchronous('statistics')]
    #[EventHandler(endpointId: 'betStats')]
    public function notify(BetPlaced $event): void
    {

    }
}
