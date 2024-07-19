<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service;

use Ecotone\Modelling\Attribute\EventHandler;
use stdClass;

/**
 * licence Apache-2.0
 */
class ServiceEventHandlerWithListenToToRegex
{
    #[EventHandler('order.*', 'eventHandler')]
    public function execute(stdClass $class): void
    {
    }
}
