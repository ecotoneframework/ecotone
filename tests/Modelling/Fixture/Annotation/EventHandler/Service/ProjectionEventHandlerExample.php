<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service;

use Ecotone\EventSourcing\Attribute\Projection;
use Ecotone\Modelling\Attribute\EventHandler;
use stdClass;

#[Projection('some', 'some')]
class ProjectionEventHandlerExample
{
    #[EventHandler(endpointId: 'eventHandler')]
    public function execute(stdClass $class): int
    {
    }
}
