<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\EventHandler;
use stdClass;

#[Aggregate]
class AggregateEventHandlerWithListenTo
{
    #[EventHandler("execute", endpointId: "eventHandler")]
    public function execute(): void
    {

    }
}