<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service;

use Ecotone\Api\Attribute\EventHandler;
use stdClass;

/**
 * licence Apache-2.0
 */
class ServiceEventHandlerWithClass
{
    #[EventHandler(endpointId: 'eventHandler')]
    public function execute(stdClass $class): int
    {
    }
}
