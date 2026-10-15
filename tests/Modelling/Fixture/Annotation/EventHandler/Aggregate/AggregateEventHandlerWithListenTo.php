<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Aggregate;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\EventHandler;

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
