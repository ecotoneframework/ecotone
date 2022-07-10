<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Attribute\Aggregate;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\QueryHandler;

class ServiceEventHandlerWithClass
{
    #[EventHandler(endpointId: "eventHandler")]
    public function execute(\stdClass $class) : int
    {

    }
}