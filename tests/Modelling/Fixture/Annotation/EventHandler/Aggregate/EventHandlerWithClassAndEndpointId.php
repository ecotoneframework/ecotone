<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Aggregate;

use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\EventHandler;
use stdClass;

/**
 * @Aggregate()
 */
class EventHandlerWithClassAndEndpointId
{
    /**
     * @EventHandler(endpointId="endpointId")
     */
    public function execute(stdClass $class): int
    {

    }
}