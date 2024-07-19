<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\EventHandler;

#[Aggregate]
/**
 * licence Apache-2.0
 */
class AggregateEventHandlerWithListenTo
{
    #[EventHandler('execute', endpointId: 'eventHandler')]
    public function execute(): void
    {
    }
}
