<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service;

use Ecotone\Modelling\Attribute\EventHandler;
use stdClass;

class ServiceEventHandlerWithClass
{
    #[EventHandler(endpointId: 'eventHandler')]
    public function execute(stdClass $class): int
    {
    }
}
