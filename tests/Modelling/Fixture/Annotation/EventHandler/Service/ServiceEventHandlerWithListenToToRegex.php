<?php


namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service;

use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;

class ServiceEventHandlerWithListenToToRegex
{
    #[EventHandler("order.*", "eventHandler")]
    public function execute(\stdClass $class) : void
    {

    }
}