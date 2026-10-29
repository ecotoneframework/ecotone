<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service;

use Ecotone\Modelling\Attribute\EventHandler;

/**
 * licence Apache-2.0
 */
class ServiceEventHandlerWithListenTo
{
    #[EventHandler('execute', 'eventHandler')]
    public function execute(): void
    {
    }
}
