<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

/**
 * @MessageEndpoint()
 */
class ServiceEventHandlerWithListenToToRegex
{
    /**
     * @EventHandler(listenTo="order.*", endpointId="eventHandler")
     */
    public function execute(\stdClass $class) : void
    {

    }
}