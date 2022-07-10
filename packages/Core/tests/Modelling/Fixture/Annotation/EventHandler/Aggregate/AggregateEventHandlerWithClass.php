<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Aggregate;

use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\EventHandler;
use stdClass;

#[Aggregate]
class AggregateEventHandlerWithClass
{
    #[EventHandler(endpointId: "eventHandler")]
    public function execute(stdClass $class): int
    {

    }
}