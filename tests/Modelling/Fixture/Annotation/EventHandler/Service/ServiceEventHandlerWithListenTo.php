<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service;

use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

class ServiceEventHandlerWithListenTo
{
    /**
     * @EventHandler(listenTo="execute", endpointId="eventHandler")
     */
    public function execute() : void
    {

    }
}