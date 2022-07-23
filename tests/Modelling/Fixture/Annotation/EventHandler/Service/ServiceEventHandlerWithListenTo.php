<?php

namespace Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service;

use Ecotone\Modelling\Attribute\EventHandler;

class ServiceEventHandlerWithListenTo
{
    #[EventHandler('execute', 'eventHandler')]
    public function execute(): void
    {
    }
}
