<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Aggregate;

use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\EventHandler;
use stdClass;

/**
 * @Aggregate()
 */
class AggregateEventHandlerWithListenTo
{
    #[EventHandler("execute", endpointId: "eventHandler")]
    public function execute(): void
    {

    }
}