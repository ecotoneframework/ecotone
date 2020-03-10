<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

/**
 * @MessageEndpoint()
 */
class EventHandlerWithClassAndEndpointId
{
    /**
     * @EventHandler(endpointId="endpointId")
     */
    public function execute(\stdClass $class) : int
    {

    }
}